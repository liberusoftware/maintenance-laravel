<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Procurement\Models\PurchaseOrder;

final class PlacePurchaseOrder
{
    public function handle(int $teamId, PurchaseOrder $order): PurchaseOrder
    {
        abort_unless((int) $order->team_id === $teamId, 404);
        if ($order->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Only draft purchase orders can be placed.']);
        }

        $order->forceFill(['status' => 'ordered', 'ordered_at' => $order->ordered_at ?? now()])->save();

        return $order->refresh();
    }
}
