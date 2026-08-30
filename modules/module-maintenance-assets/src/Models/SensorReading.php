<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SensorReading extends Model
{
    protected $table = 'maintenance_asset_sensor_readings';

    protected $fillable = ['asset_id', 'sensor_type', 'metric_name', 'value', 'unit', 'metadata', 'status', 'reading_at'];

    protected $casts = ['asset_id' => 'integer', 'value' => 'decimal:6', 'metadata' => 'array', 'reading_at' => 'datetime'];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('reading_at', '>=', now()->subHours(max(0, $hours)));
    }

    public function scopeForMetric(Builder $query, string $metric): Builder
    {
        return $query->where('metric_name', $metric);
    }

    public function scopeAbnormal(Builder $query): Builder
    {
        return $query->whereIn('status', ['warning', 'critical', 'error']);
    }

    public function isAbnormal(): bool
    {
        return in_array($this->status, ['warning', 'critical', 'error'], true);
    }
}
