<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Queries;

use Illuminate\Support\Collection;
use Liberu\Modules\Maintenance\Inventory\Models\StockItem;

final class ReorderRecommendations
{
    public function handle(int $teamId): Collection
    {
        return StockItem::query()->where('team_id', $teamId)->lowStock()->orderBy('name')->get()->map(fn (StockItem $item): array => [
            'stock_item_id' => $item->getKey(),
            'part_number' => $item->part_number,
            'name' => $item->name,
            'available_quantity' => $item->availableQuantity(),
            'reorder_level' => $item->reorder_level,
            'recommended_quantity' => max(0, (int) $item->reorder_quantity - $item->availableQuantity()),
            'supplier_name' => $item->supplier_name,
            'lead_time_days' => $item->lead_time_days,
        ]);
    }
}
