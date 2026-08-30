<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Inventory\Models\InventoryLocation;
use Liberu\Modules\Maintenance\Inventory\Models\StockItem;
use Liberu\Modules\Maintenance\Inventory\Models\StockLevel;
use Liberu\Modules\Maintenance\Inventory\Models\StockMovement;

final class SetStockLevel
{
    public function handle(int $teamId, StockItem $item, InventoryLocation $location, int $quantity, ?int $userId = null): StockLevel
    {
        abort_unless((int) $item->team_id === $teamId && (int) $location->team_id === $teamId, 404);
        if ($quantity < 0) {
            throw ValidationException::withMessages(['quantity' => 'Stock cannot be negative.']);
        }

        return DB::transaction(function () use ($teamId, $item, $location, $quantity, $userId): StockLevel {
            $level = StockLevel::query()->where('stock_item_id', $item->getKey())->where('location_id', $location->getKey())->lockForUpdate()->first();
            $before = (int) ($level?->quantity ?? 0);
            if ($quantity < (int) ($level?->reserved_quantity ?? 0)) {
                throw ValidationException::withMessages(['quantity' => 'Stock cannot fall below the reserved quantity.']);
            }
            $level ??= new StockLevel(['team_id' => $teamId, 'stock_item_id' => $item->getKey(), 'location_id' => $location->getKey(), 'reserved_quantity' => 0]);
            $level->quantity = $quantity;
            $level->save();
            $item->increment('quantity', $quantity - $before);
            StockMovement::query()->create(['team_id' => $teamId, 'stock_item_id' => $item->getKey(), 'user_id' => $userId, 'delta' => $quantity - $before, 'quantity_before' => (int) $item->quantity - ($quantity - $before), 'quantity_after' => (int) $item->quantity, 'reason' => 'location_count', 'notes' => 'Location: '.$location->code]);

            return $level->refresh();
        });
    }
}
