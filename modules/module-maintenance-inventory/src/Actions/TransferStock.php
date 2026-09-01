<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Inventory\Models\InventoryLocation;
use Liberu\Modules\Maintenance\Inventory\Models\StockItem;
use Liberu\Modules\Maintenance\Inventory\Models\StockLevel;
use Liberu\Modules\Maintenance\Inventory\Models\StockMovement;

final class TransferStock
{
    public function handle(int $teamId, StockItem $item, InventoryLocation $from, InventoryLocation $to, int $quantity, ?int $userId = null, ?string $notes = null): void
    {
        abort_unless((int) $item->team_id === $teamId && (int) $from->team_id === $teamId && (int) $to->team_id === $teamId, 404);
        if ($from->is($to) || $quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'A positive transfer between two different locations is required.']);
        }

        DB::transaction(function () use ($teamId, $item, $from, $to, $quantity, $userId, $notes): void {
            $source = StockLevel::query()->where('stock_item_id', $item->getKey())->where('location_id', $from->getKey())->lockForUpdate()->first();
            if ($source === null || $source->availableQuantity() < $quantity) {
                throw ValidationException::withMessages(['quantity' => 'The source location does not have enough available stock.']);
            }
            $destination = StockLevel::query()->where('stock_item_id', $item->getKey())->where('location_id', $to->getKey())->lockForUpdate()->first();
            $destination ??= new StockLevel(['team_id' => $teamId, 'stock_item_id' => $item->getKey(), 'location_id' => $to->getKey(), 'quantity' => 0, 'reserved_quantity' => 0]);
            $sourceBefore = (int) $source->quantity;
            $destinationBefore = (int) $destination->quantity;
            $source->quantity = $sourceBefore - $quantity;
            $source->save();
            $destination->quantity = $destinationBefore + $quantity;
            $destination->save();
            StockMovement::query()->create(['team_id' => $teamId, 'stock_item_id' => $item->getKey(), 'user_id' => $userId, 'delta' => -$quantity, 'quantity_before' => $sourceBefore, 'quantity_after' => $sourceBefore - $quantity, 'reason' => 'transfer_out', 'notes' => trim(($notes ?? '').' From: '.$from->code.' To: '.$to->code)]);
            StockMovement::query()->create(['team_id' => $teamId, 'stock_item_id' => $item->getKey(), 'user_id' => $userId, 'delta' => $quantity, 'quantity_before' => $destinationBefore, 'quantity_after' => $destinationBefore + $quantity, 'reason' => 'transfer_in', 'notes' => trim(($notes ?? '').' From: '.$from->code.' To: '.$to->code)]);
        });
    }
}
