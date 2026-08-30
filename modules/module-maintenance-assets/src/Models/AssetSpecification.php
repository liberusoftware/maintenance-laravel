<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class AssetSpecification extends Model
{
    protected $table = 'maintenance_asset_specifications';
    protected $fillable = ['team_id', 'asset_id', 'key', 'value', 'unit', 'sort_order'];
    protected $casts = ['team_id' => 'integer', 'asset_id' => 'integer', 'sort_order' => 'integer'];

    public function team(): BelongsTo { return $this->belongsTo(Team::class); }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
}
