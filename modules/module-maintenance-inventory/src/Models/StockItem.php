<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class StockItem extends Model
{
    protected $table = 'maintenance_stock_items';

    protected $fillable = ['team_id', 'part_number', 'name', 'location', 'quantity', 'reorder_level', 'unit'];

    protected $casts = ['team_id' => 'integer', 'quantity' => 'integer', 'reserved_quantity' => 'integer', 'reorder_level' => 'integer'];

    public function availableQuantity(): int
    {
        return (int) $this->quantity - (int) $this->reserved_quantity;
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereRaw('(quantity - reserved_quantity) <= reorder_level');
    }

    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->whereRaw('(quantity - reserved_quantity) <= 0');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
