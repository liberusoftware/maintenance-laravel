<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Modules\Maintenance\Inventory\Models\StockItem;

final class DeleteStockItem
{
    public function handle(int $teamId, StockItem $item): void
    {
        abort_unless((int) $item->team_id === $teamId, 404);
        DB::transaction(static fn (): bool => (bool) $item->delete());
    }
}
