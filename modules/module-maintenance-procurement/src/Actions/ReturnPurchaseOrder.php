<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Actions;

use Liberu\Modules\Maintenance\Procurement\Models\PurchaseOrder;
use Liberu\Modules\Maintenance\Procurement\Models\PurchaseOrderReturn;

final class ReturnPurchaseOrder
{
    public function handle(int $teamId, PurchaseOrder $order, array $attributes, ?int $userId = null): PurchaseOrderReturn
    {
        abort_unless((int) $order->team_id === $teamId, 404);

        return PurchaseOrderReturn::create(['team_id' => $teamId, 'purchase_order_id' => $order->getKey(), 'returned_by' => $userId, 'status' => 'requested', 'returned_at' => $attributes['returned_at'] ?? now(), 'items' => $attributes['items'], 'reason' => $attributes['reason'] ?? null]);
    }
}
