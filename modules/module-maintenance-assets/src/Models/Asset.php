<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class Asset extends Model
{
    protected $table = 'maintenance_assets';

    protected $fillable = ['team_id', 'name', 'description', 'code', 'category', 'serial_number', 'model', 'manufacturer', 'location', 'purchase_date', 'warranty_expiry', 'notes', 'condition', 'criticality', 'status', 'qr_code', 'barcode', 'sensor_enabled', 'sensor_type', 'sensor_id', 'sensor_config', 'last_sensor_reading_at', 'metadata'];

    protected $casts = ['team_id' => 'integer', 'purchase_date' => 'date', 'warranty_expiry' => 'date', 'sensor_enabled' => 'boolean', 'sensor_config' => 'array', 'last_sensor_reading_at' => 'datetime', 'metadata' => 'array'];

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
        return $query->sensorEnabled()->where('metadata->sensor_status', 'critical')->where('last_sensor_reading_at', '>=', now()->subDay());
    }

    public function getHealthStatusAttribute(): string
    {
        if (! $this->sensor_enabled) {
            return 'unknown';
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
}
