<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Services;

use Illuminate\Support\Collection;
use Liberu\Modules\Maintenance\Assets\Models\Asset;

final class SensorHealthService
{
    public function summary(Asset $asset, int $hours = 24): array
    {
        $readings = $asset->sensorReadings()->recent($hours)->latest('reading_at')->get();
        if ($readings->isEmpty()) {
            return ['status' => 'no_data', 'metrics' => [], 'alerts_count' => 0];
        }

        $metrics = $readings->groupBy('metric_name')->map(function (Collection $items, string $name): array {
            return ['name' => $name, 'current' => $items->first()->value, 'average' => round((float) $items->avg('value'), 6), 'min' => $items->min('value'), 'max' => $items->max('value'), 'unit' => $items->first()->unit, 'readings_count' => $items->count(), 'status' => $items->first()->status];
        })->values()->all();

        return ['status' => $asset->health_status, 'metrics' => $metrics, 'alerts_count' => $readings->filter->isAbnormal()->count(), 'last_reading_at' => $readings->first()->reading_at];
    }

    public function insights(Asset $asset, int $days = 30): array
    {
        $readings = $asset->sensorReadings()->where('reading_at', '>=', now()->subDays(max(0, $days)))->oldest('reading_at')->get();
        if ($readings->count() < 2) {
            return ['trend' => 'insufficient_data', 'prediction' => null, 'confidence' => 0];
        }

        return $readings->groupBy('metric_name')->mapWithKeys(fn (Collection $items, string $metric): array => [$metric => $this->trend($items)])->all();
    }

    public function dashboard(int $teamId): array
    {
        $assets = Asset::query()->where('team_id', $teamId)->sensorEnabled()->get();
        $counts = ['healthy' => 0, 'warning' => 0, 'critical' => 0, 'no_data' => 0];
        foreach ($assets as $asset) {
            $status = $asset->health_status;
            $counts[array_key_exists($status, $counts) ? $status : 'no_data']++;
        }

        return ['total_monitored' => $assets->count(), ...$counts, 'critical_assets' => $assets->where('health_status', 'critical')->map(fn (Asset $asset): array => ['id' => $asset->getKey(), 'name' => $asset->name, 'location' => $asset->location, 'last_reading_at' => $asset->last_sensor_reading_at])->values()->all()];
    }

    private function trend(Collection $readings): array
    {
        $values = $readings->pluck('value')->map(fn ($value): float => (float) $value)->values()->all();
        $count = count($values);
        $sumX = ($count - 1) * $count / 2;
        $sumX2 = ($count - 1) * $count * (2 * $count - 1) / 6;
        $sumY = array_sum($values);
        $sumXY = 0.0;
        foreach ($values as $index => $value) {
            $sumXY += $index * $value;
        }
        $denominator = $count * $sumX2 - $sumX ** 2;
        $slope = $denominator == 0.0 ? 0.0 : ($count * $sumXY - $sumX * $sumY) / $denominator;
        $direction = abs($slope) > 0.1 ? ($slope > 0 ? 'increasing' : 'decreasing') : 'stable';

        return ['trend' => $direction, 'direction' => $direction, 'rate_of_change' => round($slope, 6)];
    }
}
