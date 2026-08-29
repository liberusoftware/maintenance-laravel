<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StockMovement extends Model
{
    protected $table = 'maintenance_stock_movements';

    protected $fillable = ['team_id', 'stock_item_id', 'user_id', 'delta', 'quantity_before', 'quantity_after', 'reason', 'notes'];

    protected function casts(): array
    {
        return ['team_id' => 'integer', 'stock_item_id' => 'integer', 'user_id' => 'integer', 'delta' => 'integer', 'quantity_before' => 'integer', 'quantity_after' => 'integer'];
    }

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class);
    }
}
