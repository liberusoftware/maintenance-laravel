<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Procurement\Models\PurchaseOrder;
use Liberu\Modules\Maintenance\Procurement\Models\PurchaseOrderReceipt;

final class ReceivePurchaseOrder
{
    public function handle(int $teamId, PurchaseOrder $order, array $attributes, ?int $receiverId = null): PurchaseOrder
    {
        abort_unless((int) $order->team_id === $teamId, 404);
        if (! in_array($order->status, ['ordered', 'partially_received'], true)) {
            throw ValidationException::withMessages(['status' => 'Only ordered purchase orders can receive goods.']);
        }
        if (! is_array($attributes['items'] ?? null) || $attributes['items'] === []) {
            throw ValidationException::withMessages(['items' => 'At least one received item is required.']);
        }

        return DB::transaction(function () use ($teamId, $order, $attributes, $receiverId): PurchaseOrder {
            PurchaseOrderReceipt::create(['team_id' => $teamId, 'purchase_order_id' => $order->getKey(), 'received_by' => $receiverId, 'received_at' => $attributes['received_at'] ?? now(), 'items' => $attributes['items'], 'notes' => $attributes['notes'] ?? null]);
            $order->forceFill(['status' => 'received', 'received_at' => $attributes['received_at'] ?? now()])->save();

            return $order->refresh();
        });
    }
}
