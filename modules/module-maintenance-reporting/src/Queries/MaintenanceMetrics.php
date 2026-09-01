<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Report\Queries;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrder;

final class MaintenanceMetrics
{
    public function handle(int $teamId, ?CarbonInterface $start = null, ?CarbonInterface $end = null): array
    {
        $start ??= CarbonImmutable::now()->subDays(30);
        $end ??= CarbonImmutable::now();
        $orders = $this->orders($teamId, $start, $end)->get();
        $completed = $orders->filter(fn (WorkOrder $order): bool => $order->status === 'completed' && $order->started_at !== null && $order->completed_at !== null);
        $responseOrders = $orders->filter(fn (WorkOrder $order): bool => $order->submitted_at !== null && $order->started_at !== null);

        return [
            'period' => ['start' => $start->toISOString(), 'end' => $end->toISOString()],
            'backlog' => $orders->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'total_work_orders' => $orders->count(),
            'completed_work_orders' => $completed->count(),
            'mttr_hours' => $this->averageHours($completed, 'started_at', 'completed_at'),
            'average_response_hours' => $this->averageHours($responseOrders, 'submitted_at', 'started_at'),
            'first_time_fix_rate' => $this->firstTimeFixRate($completed),
            'downtime_hours' => round((float) $completed->sum(fn (WorkOrder $order): float => $this->hoursBetween($order->started_at, $order->completed_at)), 2),
            'by_priority' => $orders->groupBy('priority')->map->count()->all(),
            'by_status' => $orders->groupBy('status')->map->count()->all(),
            'by_day' => $this->byDay($orders),
        ];
    }

    private function orders(int $teamId, CarbonInterface $start, CarbonInterface $end)
    {
        return WorkOrder::query()->where('team_id', $teamId)->where(function ($query) use ($start, $end): void {
            $query->whereBetween('submitted_at', [$start, $end])->orWhereBetween('completed_at', [$start, $end]);
        });
    }

    private function averageHours(Collection $orders, string $from, string $to): float
    {
        return $orders->isEmpty() ? 0.0 : round((float) $orders->avg(fn (WorkOrder $order): float => $this->hoursBetween($order->{$from}, $order->{$to})), 2);
    }

    private function hoursBetween(?CarbonInterface $from, ?CarbonInterface $to): float
    {
        return $from === null || $to === null ? 0.0 : $from->diffInMinutes($to) / 60;
    }

    private function firstTimeFixRate(Collection $completed): float
    {
        if ($completed->isEmpty()) {
            return 0.0;
        }

        $firstTimeFixes = $completed->filter(function (WorkOrder $order): bool {
            return ! collect(data_get($order->metadata, 'status_history', []))->contains(fn (array $transition): bool => in_array($transition['to'] ?? null, ['blocked', 'reopened'], true));
        })->count();

        return round(($firstTimeFixes / $completed->count()) * 100, 2);
    }

    private function byDay(Collection $orders): array
    {
        return $orders->filter(fn (WorkOrder $order): bool => $order->submitted_at !== null)
            ->groupBy(fn (WorkOrder $order): string => $order->submitted_at->toDateString())
            ->map(fn (Collection $day): array => ['total' => $day->count(), 'completed' => $day->where('status', 'completed')->count()])
            ->sortKeys()->all();
    }
}
