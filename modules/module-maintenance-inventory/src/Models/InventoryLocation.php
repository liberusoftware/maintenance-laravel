<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class InventoryLocation extends Model
{
    protected $table = 'maintenance_inventory_locations';
    protected $fillable = ['team_id', 'code', 'name', 'type', 'is_active'];
    protected $casts = ['team_id' => 'integer', 'is_active' => 'boolean'];

    public function team(): BelongsTo { return $this->belongsTo(Team::class); }
    public function levels(): HasMany { return $this->hasMany(StockLevel::class, 'location_id'); }
}
