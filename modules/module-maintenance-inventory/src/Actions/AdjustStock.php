<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Inventory\Models\StockItem;
use Liberu\Modules\Maintenance\Inventory\Models\StockMovement;

class AdjustStock
{
    public function handle(int $teamId, StockItem $item, int $delta, string $reason = 'adjustment', ?int $userId = null, ?string $notes = null): StockItem
    {
        if ((int) $item->team_id !== $teamId) {
            abort(404);
        }

        return DB::transaction(function () use ($teamId, $item, $delta, $reason, $userId, $notes) {
            $item->refresh();
            $before = (int) $item->quantity;
            $next = $before + $delta;
            if ($next < 0) {
                throw ValidationException::withMessages(['quantity' => 'Stock cannot become negative.']);
            }
            if ($next < (int) $item->reserved_quantity) {
                throw ValidationException::withMessages(['quantity' => 'Stock cannot fall below the reserved quantity.']);
            }
            $item->quantity = $next;
            $item->save();
            StockMovement::query()->create(['team_id' => $teamId, 'stock_item_id' => $item->getKey(), 'user_id' => $userId, 'delta' => $delta, 'quantity_before' => $before, 'quantity_after' => $next, 'reason' => trim($reason) === '' ? 'adjustment' : trim($reason), 'notes' => $notes]);

            return $item->refresh();
        });
    }
}
