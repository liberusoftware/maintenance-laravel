<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Liberu\Modules\OrganizationsTeams\Models\Team;

final class AssetMeter extends Model
{
    protected $table = 'maintenance_asset_meters';

    protected $fillable = ['team_id', 'asset_id', 'name', 'unit', 'current_value', 'last_reading_at', 'is_active'];

    protected function casts(): array
    {
        return ['team_id' => 'integer', 'asset_id' => 'integer', 'current_value' => 'float', 'last_reading_at' => 'datetime', 'is_active' => 'boolean'];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function readings(): HasMany
    {
        return $this->hasMany(AssetMeterReading::class, 'meter_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
