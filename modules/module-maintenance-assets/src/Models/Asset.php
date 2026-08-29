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

    protected $fillable = ['team_id', 'name', 'code', 'category', 'serial_number', 'condition', 'criticality', 'status', 'qr_code', 'barcode', 'metadata'];

    protected $casts = ['team_id' => 'integer', 'metadata' => 'array'];

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

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
