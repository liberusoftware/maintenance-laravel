<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Procurement\Models\PurchaseOrder;
use Liberu\Modules\Maintenance\Procurement\Models\PurchaseRequest;

final class CreatePurchaseOrder
{
    public function handle(int $teamId, array $attributes, ?PurchaseRequest $request = null): PurchaseOrder
    {
        if ($request !== null && ((int) $request->team_id !== $teamId || $request->status !== 'approved')) {
            throw ValidationException::withMessages(['purchase_request_id' => 'Only an approved request from this team can become an order.']);
        }
        $number = trim((string) ($attributes['order_number'] ?? ''));
        if ($number === '') {
            throw ValidationException::withMessages(['order_number' => 'An order number is required.']);
        }
        if (PurchaseOrder::query()->where('team_id', $teamId)->where('order_number', $number)->exists()) {
            throw ValidationException::withMessages(['order_number' => 'The order number is already used by this team.']);
        }

        return DB::transaction(fn (): PurchaseOrder => PurchaseOrder::create(array_merge($attributes, ['team_id' => $teamId, 'purchase_request_id' => $request?->getKey() ?? $attributes['purchase_request_id'] ?? null, 'order_number' => $number, 'status' => $attributes['status'] ?? 'draft', 'currency' => strtoupper((string) ($attributes['currency'] ?? 'USD'))])));
    }
}
