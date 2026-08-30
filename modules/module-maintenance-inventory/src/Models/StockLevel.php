<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLevel extends Model
{
    protected $table = 'maintenance_stock_levels';

    protected $fillable = ['team_id', 'stock_item_id', 'location_id', 'quantity', 'reserved_quantity'];

    protected $casts = ['team_id' => 'integer', 'stock_item_id' => 'integer', 'location_id' => 'integer', 'quantity' => 'integer', 'reserved_quantity' => 'integer'];

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id');
    }

    public function availableQuantity(): int
    {
        return (int) $this->quantity - (int) $this->reserved_quantity;
    }
}
