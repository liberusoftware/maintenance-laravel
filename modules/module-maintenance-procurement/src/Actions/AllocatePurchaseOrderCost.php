<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Procurement\Models\PurchaseOrder;
use Liberu\Modules\Maintenance\Procurement\Models\PurchaseOrderCostAllocation;

final class AllocatePurchaseOrderCost
{
    public function handle(int $teamId, PurchaseOrder $order, array $attributes): PurchaseOrderCostAllocation
    {
        abort_unless((int) $order->team_id === $teamId, 404);
        $amount = (float) $attributes['amount'];
        $allocated = (float) PurchaseOrderCostAllocation::query()->where('team_id', $teamId)->where('purchase_order_id', $order->getKey())->sum('amount');
        if ($allocated + $amount > (float) $order->amount) {
            throw ValidationException::withMessages(['amount' => 'Cost allocations cannot exceed the purchase order amount.']);
        }

        return PurchaseOrderCostAllocation::create(['team_id' => $teamId, 'purchase_order_id' => $order->getKey(), 'cost_center' => trim((string) $attributes['cost_center']), 'amount' => $amount, 'currency' => strtoupper((string) ($attributes['currency'] ?? $order->currency)), 'description' => $attributes['description'] ?? null]);
    }
}
