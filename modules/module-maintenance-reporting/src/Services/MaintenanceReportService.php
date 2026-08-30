<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Report\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Liberu\Modules\Maintenance\Assets\Models\Asset;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Models\MaintenancePlan;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrder;

final class MaintenanceReportService
{
    public function calculateMTTR(?int $teamId = null, ?CarbonInterface $start = null, ?CarbonInterface $end = null): float
    {
        $orders = $this->completedOrders($teamId, $start, $end)->get();

        return $this->averageHours($orders, 'started_at', 'completed_at');
    }

    public function calculateEquipmentUptime(int $equipmentId, ?CarbonInterface $start = null, ?CarbonInterface $end = null): float
    {
        $start ??= CarbonImmutable::now()->subDays(30);
        $end ??= CarbonImmutable::now();
        $totalMinutes = max(1, $start->diffInMinutes($end));
        $downtimeMinutes = $this->ordersForEquipment($equipmentId, $start, $end)
            ->get()
            ->sum(fn (WorkOrder $order): float => $this->overlapMinutes($order, $start, $end));

        return round(max(0, min(100, (1 - ($downtimeMinutes / $totalMinutes)) * 100)), 2);
    }

    /** @return array{parts_cost: float, labor_cost: float, total_cost: float, average_cost_per_work_order: float, total_work_orders: int} */
    public function generateCostAnalysis(?int $teamId = null, ?CarbonInterface $start = null, ?CarbonInterface $end = null): array
    {
        $orders = $this->completedOrders($teamId, $start, $end)->get();
        $partsCost = (float) $orders->sum(fn (WorkOrder $order): float => $this->metadataNumber($order, ['parts_cost', 'parts_total']));
        $laborCost = (float) $orders->sum(fn (WorkOrder $order): float => $this->laborCost($order));
        $total = $partsCost + $laborCost;
        $count = $orders->count();

        return [
            'parts_cost' => round($partsCost, 2),
            'labor_cost' => round($laborCost, 2),
            'total_cost' => round($total, 2),
            'average_cost_per_work_order' => $count === 0 ? 0.0 : round($total / $count, 2),
            'total_work_orders' => $count,
        ];
    }

    /** @return list<array<string, mixed>> */
    public function getEquipmentPerformanceMetrics(?int $teamId = null, ?CarbonInterface $start = null, ?CarbonInterface $end = null): array
    {
        $start ??= CarbonImmutable::now()->subDays(30);
        $end ??= CarbonImmutable::now();
        $assets = Asset::query()->when($teamId !== null, fn ($query) => $query->where('team_id', $teamId))->get();

        return $assets->map(function (Asset $asset) use ($start, $end): array {
            $orders = $this->ordersForEquipment((int) $asset->getKey(), $start, $end)->get();
            $cost = (float) $orders->sum(fn (WorkOrder $order): float => $this->metadataNumber($order, ['parts_cost', 'parts_total']) + $this->laborCost($order));

            return [
                'equipment_id' => $asset->getKey(),
                'equipment_name' => $asset->name,
                'serial_number' => $asset->serial_number,
                'criticality' => $asset->criticality,
                'work_order_count' => $orders->count(),
                'total_cost' => round($cost, 2),
                'average_cost' => $orders->isEmpty() ? 0.0 : round($cost / $orders->count(), 2),
                'uptime_percentage' => $this->calculateEquipmentUptime((int) $asset->getKey(), $start, $end),
                'failure_rate' => $orders->count(),
            ];
        })->sortByDesc('total_cost')->values()->all();
    }

    /** @return list<array<string, mixed>> */
    public function getTechnicianPerformanceMetrics(?int $teamId = null, ?CarbonInterface $start = null, ?CarbonInterface $end = null): array
    {
        $orders = $this->orders($teamId, $start, $end)->whereNotNull('assigned_to')->get();
        $userModel = (string) config('auth.providers.users.model');
        $users = $userModel !== '' ? $userModel::query()->whereIn('id', $orders->pluck('assigned_to')->unique())->get()->keyBy('id') : collect();

        return $orders->groupBy('assigned_to')->map(function (Collection $assigned, $userId) use ($users): array {
            $completed = $assigned->where('status', 'completed')->count();
            $user = $users->get($userId);

            return [
                'technician_id' => (int) $userId,
                'technician_name' => $user?->name ?? 'Unknown',
                'total_assigned' => $assigned->count(),
                'completed' => $completed,
                'in_progress' => $assigned->where('status', 'in_progress')->count(),
                'pending' => $assigned->whereIn('status', ['requested', 'triaged'])->count(),
                'completion_rate' => round($assigned->isEmpty() ? 0.0 : ($completed / $assigned->count()) * 100, 2),
                'average_completion_time_hours' => $this->averageHours($assigned->filter(fn (WorkOrder $order): bool => $order->started_at !== null && $order->completed_at !== null), 'started_at', 'completed_at'),
            ];
        })->sortByDesc('completion_rate')->values()->all();
    }

    /** @return array<string, mixed> */
    public function analyzeMaintenanceTrends(?int $teamId = null, int $days = 90): array
    {
        $end = CarbonImmutable::now();
        $start = $end->subDays(max(1, $days));
        $orders = $this->orders($teamId, $start, $end)->get();
        $daily = $orders->filter(fn (WorkOrder $order): bool => $order->submitted_at !== null)
            ->groupBy(fn (WorkOrder $order): string => $order->submitted_at->toDateString())
            ->map(fn (Collection $day): array => [
                'date' => $day->first()->submitted_at->toDateString(),
                'total' => $day->count(),
                'completed' => $day->where('status', 'completed')->count(),
                'urgent' => $day->where('priority', 'urgent')->count(),
                'high' => $day->where('priority', 'high')->count(),
            ])->sortKeys();
        $thisWeek = $this->orders($teamId, $end->subDays(7), $end)->count();
        $lastWeek = $this->orders($teamId, $end->subDays(14), $end->subDays(7))->count();

        return [
            'daily_data' => $daily->values()->all(),
            'week_over_week_change' => $lastWeek === 0 ? 0.0 : round((($thisWeek - $lastWeek) / $lastWeek) * 100, 2),
            'this_week_total' => $thisWeek,
            'last_week_total' => $lastWeek,
            'average_daily_work_orders' => round((float) ($daily->avg('total') ?? 0), 2),
            'peak_day' => $daily->sortByDesc('total')->first(),
        ];
    }

    /** @return array<string, mixed> */
    public function generateComprehensiveReport(?int $teamId = null, ?CarbonInterface $start = null, ?CarbonInterface $end = null): array
    {
        $start ??= CarbonImmutable::now()->subDays(30);
        $end ??= CarbonImmutable::now();
        $cost = $this->generateCostAnalysis($teamId, $start, $end);
        $equipment = $this->getEquipmentPerformanceMetrics($teamId, $start, $end);
        $technicians = $this->getTechnicianPerformanceMetrics($teamId, $start, $end);
        $overduePlans = MaintenancePlan::query()->when($teamId !== null, fn ($query) => $query->where('team_id', $teamId))->overdue()->count();
        $insights = [];
        if (count(array_filter($equipment, fn (array $item): bool => $item['total_cost'] > 5000)) > 0) {
            $insights[] = ['type' => 'warning', 'category' => 'Cost Management', 'message' => 'High-cost assets require review.', 'recommendation' => 'Evaluate repair versus replacement costs.'];
        }
        if (count(array_filter($equipment, fn (array $item): bool => $item['uptime_percentage'] < 80)) > 0) {
            $insights[] = ['type' => 'critical', 'category' => 'Equipment Reliability', 'message' => 'Assets with low uptime require attention.', 'recommendation' => 'Review preventative maintenance coverage.'];
        }
        if ($this->calculateMTTR($teamId, $start, $end) > 24) {
            $insights[] = ['type' => 'warning', 'category' => 'Response Time', 'message' => 'Average repair time exceeds 24 hours.', 'recommendation' => 'Review staffing and parts availability.'];
        }
        if ($overduePlans > 0) {
            $insights[] = ['type' => 'critical', 'category' => 'Preventative Maintenance', 'message' => "{$overduePlans} preventative plans are overdue.", 'recommendation' => 'Generate and prioritize the outstanding work orders.'];
        }

        return [
            'period' => ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString(), 'days' => $start->diffInDays($end)],
            'mttr' => $this->calculateMTTR($teamId, $start, $end),
            'cost_analysis' => $cost,
            'equipment_performance' => $equipment,
            'technician_performance' => $technicians,
            'trends' => $this->analyzeMaintenanceTrends($teamId, max(1, (int) $start->diffInDays($end))),
            'actionable_insights' => $insights,
        ];
    }

    private function orders(?int $teamId, ?CarbonInterface $start, ?CarbonInterface $end)
    {
        return WorkOrder::query()->when($teamId !== null, fn ($query) => $query->where('team_id', $teamId))
            ->when($start !== null || $end !== null, function ($query) use ($start, $end): void {
                $query->where(function ($period) use ($start, $end): void {
                    $period->when($start !== null, fn ($q) => $q->where('submitted_at', '>=', $start)->orWhere('completed_at', '>=', $start));
                    $period->when($end !== null, fn ($q) => $q->where('submitted_at', '<=', $end)->orWhere('completed_at', '<=', $end));
                });
            });
    }

    private function completedOrders(?int $teamId, ?CarbonInterface $start, ?CarbonInterface $end)
    {
        return $this->orders($teamId, $start, $end)->whereNotNull('started_at')->whereNotNull('completed_at');
    }

    private function ordersForEquipment(int $equipmentId, CarbonInterface $start, CarbonInterface $end)
    {
        return $this->orders(null, null, null)->where('equipment_id', $equipmentId)->whereNotNull('started_at')->whereNotNull('completed_at')
            ->where('started_at', '<=', $end)->where('completed_at', '>=', $start);
    }

    private function averageHours(Collection $orders, string $from, string $to): float
    {
        return $orders->isEmpty() ? 0.0 : round((float) $orders->avg(fn (WorkOrder $order): float => $order->{$from}->diffInMinutes($order->{$to}) / 60), 2);
    }

    private function overlapMinutes(WorkOrder $order, CarbonInterface $start, CarbonInterface $end): float
    {
        $from = $order->started_at->greaterThan($start) ? $order->started_at : $start;
        $to = $order->completed_at->lessThan($end) ? $order->completed_at : $end;

        return max(0, $from->diffInMinutes($to));
    }

    private function metadataNumber(WorkOrder $order, array $keys): float
    {
        foreach ($keys as $key) {
            $value = data_get($order->metadata, $key);
            if (is_numeric($value)) return (float) $value;
        }

        return 0.0;
    }

    private function laborCost(WorkOrder $order): float
    {
        $explicit = $this->metadataNumber($order, ['labor_cost', 'labor_total']);
        if ($explicit > 0) return $explicit;

        $minutes = $order->actual_minutes ?? ($order->started_at !== null && $order->completed_at !== null ? $order->started_at->diffInMinutes($order->completed_at) : 0);

        return ((float) $minutes / 60) * 50;
    }
}
