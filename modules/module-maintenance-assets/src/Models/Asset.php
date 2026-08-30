<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Liberu\Modules\OrganizationsTeams\Models\Team;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrder;

class Asset extends Model
{
    protected $table = 'maintenance_assets';

    protected $fillable = ['team_id', 'parent_id', 'category_id', 'name', 'description', 'code', 'category', 'serial_number', 'model', 'manufacturer', 'location', 'purchase_date', 'warranty_expiry', 'notes', 'condition', 'criticality', 'status', 'qr_code', 'barcode', 'sensor_enabled', 'sensor_type', 'sensor_id', 'sensor_config', 'last_sensor_reading_at', 'metadata'];

    protected $casts = ['team_id' => 'integer', 'parent_id' => 'integer', 'category_id' => 'integer', 'purchase_date' => 'date', 'warranty_expiry' => 'date', 'sensor_enabled' => 'boolean', 'sensor_config' => 'array', 'last_sensor_reading_at' => 'datetime', 'metadata' => 'array'];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 'inactive');
    }

    public function scopeUnderMaintenance(Builder $query): Builder
    {
        return $query->where('status', 'under_maintenance');
    }

    public function scopeCritical(Builder $query): Builder
    {
        return $query->where('criticality', 'critical');
    }

    public function scopeHigh(Builder $query): Builder
    {
        return $query->where('criticality', 'high');
    }

    public function scopeInCondition(Builder $query, string $condition): Builder
    {
        return $query->where('condition', trim($condition));
    }

    public function scopeSensorEnabled(Builder $query): Builder
    {
        return $query->where('sensor_enabled', true);
    }

    public function scopeUnderWarranty(Builder $query): Builder
    {
        return $query->whereNotNull('warranty_expiry')->where('warranty_expiry', '>=', now()->toDateString());
    }

    public function scopeWarrantyExpired(Builder $query): Builder
    {
        return $query->whereNotNull('warranty_expiry')->where('warranty_expiry', '<', now()->toDateString());
    }

    public function isUnderWarranty(): bool
    {
        return $this->warranty_expiry !== null && ($this->warranty_expiry->isToday() || $this->warranty_expiry->isFuture());
    }

    public function warrantyDaysRemaining(): ?int
    {
        return $this->warranty_expiry === null ? null : max(0, (int) now()->diffInDays($this->warranty_expiry, false));
    }

    public function scopeWithCriticalReadings(Builder $query): Builder
    {
        return $query->sensorEnabled()->where(function (Builder $assets): void {
            $assets->whereHas('sensorReadings', fn (Builder $readings): Builder => $readings->recent()->where('status', 'critical'))
                ->orWhere(function (Builder $legacy): void {
                    $legacy->where('metadata->sensor_status', 'critical')->where('last_sensor_reading_at', '>=', now()->subDay());
                });
        });
    }

    public function getHealthStatusAttribute(): string
    {
        if (! $this->sensor_enabled) {
            return 'unknown';
        }

        $recentStatuses = $this->recentSensorReadings()->pluck('status');
        if ($recentStatuses->contains('critical')) return 'critical';
        if ($recentStatuses->contains('warning')) return 'warning';

        if ($this->last_sensor_reading_at === null && $recentStatuses->isEmpty()) {
            return 'no_data';
        }

        return match ($this->metadata['sensor_status'] ?? null) {
            'critical' => 'critical',
            'warning' => 'warning',
            default => 'healthy',
        };
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function categoryRecord(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function specifications(): HasMany
    {
        return $this->hasMany(AssetSpecification::class)->orderBy('sort_order')->orderBy('key');
    }

    public function warranties(): HasMany
    {
        return $this->hasMany(AssetWarranty::class)->orderByDesc('expires_on');
    }

    public function history(): HasMany
    {
        return $this->hasMany(AssetHistory::class)->latest('occurred_at');
    }

    public function sensorReadings(): HasMany
    {
        return $this->hasMany(SensorReading::class);
    }

    public function recentSensorReadings(): HasMany
    {
        return $this->hasMany(SensorReading::class)->where('reading_at', '>=', now()->subHours(24))->orderByDesc('reading_at');
    }

    public function criticalSensorReadings(): HasMany
    {
        return $this->hasMany(SensorReading::class)->where('status', 'critical')->orderByDesc('reading_at');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'equipment_id');
    }

    public function meters(): HasMany
    {
        return $this->hasMany(AssetMeter::class);
    }

    public function hasActiveWorkOrders(): bool
    {
        return $this->workOrders()->whereIn('status', ['requested', 'approved', 'triaged', 'in_progress', 'blocked'])->exists();
    }

    public function canBeSetToActive(): bool
    {
        return ! $this->hasActiveWorkOrders();
    }

    public function syncStatusWithWorkOrders(): void
    {
        if ($this->hasActiveWorkOrders() && $this->status !== 'under_maintenance') {
            $this->update(['status' => 'under_maintenance']);
        } elseif (! $this->hasActiveWorkOrders() && $this->status === 'under_maintenance') {
            $this->update(['status' => 'active']);
        }
    }

    public function scopeWithWorkOrderCounts(Builder $query): Builder
    {
        return $query->withCount([
            'workOrders',
            'workOrders as pending_work_orders_count' => fn (Builder $orders): Builder => $orders->whereIn('status', ['requested', 'approved', 'triaged']),
            'workOrders as active_work_orders_count' => fn (Builder $orders): Builder => $orders->whereIn('status', ['in_progress', 'blocked']),
        ]);
    }
}
