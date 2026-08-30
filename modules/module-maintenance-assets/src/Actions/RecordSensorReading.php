<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Assets\Models\Asset;
use Liberu\Modules\Maintenance\Assets\Models\SensorReading;

final class RecordSensorReading
{
    public function handle(Asset $asset, array $attributes): SensorReading
    {
        if (! $asset->sensor_enabled) {
            throw ValidationException::withMessages(['sensor_id' => 'Sensor is not enabled for this asset.']);
        }

        $metric = trim((string) ($attributes['metric_name'] ?? ''));
        if ($metric === '' || ! is_numeric($attributes['value'] ?? null)) {
            throw ValidationException::withMessages(['metric_name' => 'A metric name and numeric value are required.']);
        }

        $value = (float) $attributes['value'];
        $thresholds = data_get($asset->sensor_config, "thresholds.{$metric}", []);
        $status = $this->status($value, is_array($thresholds) ? $thresholds : []);

        return DB::transaction(function () use ($asset, $attributes, $metric, $value, $status): SensorReading {
            $reading = SensorReading::create([
                'asset_id' => $asset->getKey(),
                'sensor_type' => $attributes['sensor_type'] ?? $asset->sensor_type,
                'metric_name' => $metric,
                'value' => $value,
                'unit' => $attributes['unit'] ?? null,
                'metadata' => $attributes['metadata'] ?? null,
                'status' => $status,
                'reading_at' => $attributes['reading_at'] ?? now(),
            ]);

            $metadata = array_merge($asset->metadata ?? [], ['sensor_status' => $status]);
            $asset->forceFill(['last_sensor_reading_at' => $reading->reading_at, 'metadata' => $metadata])->save();

            return $reading->refresh();
        });
    }

    private function status(float $value, array $thresholds): string
    {
        foreach ([['critical_min', '<'], ['critical_max', '>'], ['warning_min', '<'], ['warning_max', '>']] as [$key, $operator]) {
            if (isset($thresholds[$key]) && ($operator === '<' ? $value < (float) $thresholds[$key] : $value > (float) $thresholds[$key])) {
                return str_starts_with($key, 'critical') ? 'critical' : 'warning';
            }
        }

        return 'normal';
    }
}
