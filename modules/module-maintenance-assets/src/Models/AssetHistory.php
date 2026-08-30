<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class AssetHistory extends Model
{
    protected $table = 'maintenance_asset_history';

    protected $fillable = ['team_id', 'asset_id', 'actor_id', 'type', 'note', 'metadata', 'occurred_at'];

    protected $casts = ['team_id' => 'integer', 'asset_id' => 'integer', 'actor_id' => 'integer', 'metadata' => 'array', 'occurred_at' => 'datetime'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
