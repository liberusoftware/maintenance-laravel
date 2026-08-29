<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class StockItem extends Model
{
    protected $table = 'maintenance_stock_items';

    protected $fillable = ['team_id', 'part_number', 'name', 'location', 'quantity', 'reorder_level', 'unit'];

    protected $casts = ['team_id' => 'integer', 'quantity' => 'integer', 'reorder_level' => 'integer'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
