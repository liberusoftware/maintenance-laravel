<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Inventory\Models\StockItem;

final class ReleaseReservedStock
{
    public function handle(int $teamId, StockItem $item, int $quantity): StockItem
    {
        abort_unless((int) $item->team_id === $teamId, 404);
        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'The release quantity must be positive.']);
        }

        return DB::transaction(function () use ($item, $quantity): StockItem {
            $item = StockItem::query()->lockForUpdate()->findOrFail($item->getKey());
            if ($quantity > (int) $item->reserved_quantity) {
                throw ValidationException::withMessages(['quantity' => 'The release exceeds reserved stock.']);
            }
            $item->decrement('reserved_quantity', $quantity);

            return $item->refresh();
        });
    }
}
