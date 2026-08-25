<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Inventory\Models\StockItem;

class AdjustStock
{
    public function handle(int $teamId, StockItem $item, int $delta): StockItem
    {
        if ((int) $item->team_id !== $teamId) {
            abort(404);
        }

        return DB::transaction(function () use ($item, $delta) {
            $item->refresh();
            $next = (int) $item->quantity + $delta;
            if ($next < 0) {
                throw ValidationException::withMessages(['quantity' => 'Stock cannot become negative.']);
            }$item->quantity = $next;
            $item->save();

            return $item->refresh();
        });
    }
}
