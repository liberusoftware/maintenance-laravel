<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class AssetWarranty extends Model
{
    protected $table = 'maintenance_asset_warranties';

    protected $fillable = ['team_id', 'asset_id', 'provider', 'reference', 'starts_on', 'expires_on', 'terms', 'status'];

    protected $casts = ['team_id' => 'integer', 'asset_id' => 'integer', 'starts_on' => 'date', 'expires_on' => 'date'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('status', 'active')->whereDate('expires_on', '>=', now()->toDateString());
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereDate('expires_on', '<', now()->toDateString());
    }
}
