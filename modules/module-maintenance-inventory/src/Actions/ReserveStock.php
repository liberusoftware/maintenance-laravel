<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Inventory\Models\StockItem;

final class ReserveStock
{
    public function handle(int $teamId, StockItem $item, int $quantity): StockItem
    {
        abort_unless((int) $item->team_id === $teamId, 404);
        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'The reservation quantity must be positive.']);
        }

        return DB::transaction(function () use ($item, $quantity): StockItem {
            $item = StockItem::query()->lockForUpdate()->findOrFail($item->getKey());
            $available = (int) $item->quantity - (int) $item->reserved_quantity;
            if ($quantity > $available) {
                throw ValidationException::withMessages(['quantity' => 'The reservation exceeds available stock.']);
            }
            $item->increment('reserved_quantity', $quantity);

            return $item->refresh();
        });
    }
}
