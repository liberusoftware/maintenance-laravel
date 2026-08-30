<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Actions;

use Liberu\Modules\Maintenance\Inventory\Models\StockItem;

final class IssueStock
{
    public function __construct(private readonly AdjustStock $adjustStock) {}

    public function handle(int $teamId, StockItem $item, int $quantity, ?int $userId = null, ?string $notes = null): StockItem
    {
        return $this->adjustStock->handle($teamId, $item, -$quantity, 'issue', $userId, $notes);
    }
}
