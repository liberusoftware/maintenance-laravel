<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Procurement\Actions\CreatePurchaseOrder;
use Liberu\Modules\Maintenance\Procurement\Actions\PlacePurchaseOrder;
use Liberu\Modules\Maintenance\Procurement\Actions\ReceivePurchaseOrder;
use Liberu\Modules\Maintenance\Procurement\Models\PurchaseOrder;
use Liberu\Modules\Maintenance\Procurement\Models\PurchaseRequest;

final class PurchaseOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);

        return response()->json(['data' => PurchaseOrder::query()->where('team_id', $teamId)->with('receipts')->latest()->get()->map(fn (PurchaseOrder $order): array => $this->resource($order))->values()]);
    }

    public function store(Request $request, CreatePurchaseOrder $create): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        $data = $request->validate(['purchase_request_id' => ['nullable', 'integer'], 'order_number' => ['required', 'string', 'max:80'], 'supplier_name' => ['nullable', 'string', 'max:255'], 'amount' => ['required', 'numeric', 'min:0'], 'currency' => ['nullable', 'string', 'size:3'], 'ordered_at' => ['nullable', 'date'], 'items' => ['nullable', 'array'], 'metadata' => ['nullable', 'array']]);
        $purchaseRequest = isset($data['purchase_request_id']) ? PurchaseRequest::query()->where('team_id', $teamId)->findOrFail($data['purchase_request_id']) : null;

        return response()->json(['data' => $this->resource($create->handle($teamId, $data, $purchaseRequest))], 201);
    }

    public function show(Request $request, string $purchaseOrder): JsonResponse
    {
        $purchaseOrder = $this->forCurrentTeam($request, $purchaseOrder);

        return response()->json(['data' => $this->resource($purchaseOrder->load('receipts'))]);
    }

    public function receive(Request $request, string $purchaseOrder, ReceivePurchaseOrder $receive): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        $purchaseOrder = $this->forCurrentTeam($request, $purchaseOrder);
        $data = $request->validate(['items' => ['required', 'array', 'min:1'], 'received_at' => ['nullable', 'date'], 'notes' => ['nullable', 'string', 'max:10000']]);

        return response()->json(['data' => $this->resource($receive->handle($teamId, $purchaseOrder, $data, (int) $request->user()->getKey()))]);
    }

    public function place(Request $request, string $purchaseOrder, PlacePurchaseOrder $place): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        $purchaseOrder = $this->forCurrentTeam($request, $purchaseOrder);

        return response()->json(['data' => $this->resource($place->handle($teamId, $purchaseOrder))]);
    }

    private function teamId(Request $request): ?int
    {
        return $request->user()?->currentTeam?->getKey() === null ? null : (int) $request->user()->currentTeam->getKey();
    }

    private function forCurrentTeam(Request $request, string $id): PurchaseOrder
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);

        return PurchaseOrder::query()->where('team_id', $teamId)->findOrFail($id);
    }

    private function resource(PurchaseOrder $order): array
    {
        return ['id' => (string) $order->getKey(), 'type' => 'maintenance-purchase-order', 'attributes' => ['order_number' => $order->order_number, 'purchase_request_id' => $order->purchase_request_id, 'supplier_name' => $order->supplier_name, 'amount' => $order->amount, 'currency' => $order->currency, 'status' => $order->status, 'ordered_at' => $order->ordered_at?->toISOString(), 'received_at' => $order->received_at?->toISOString(), 'items' => $order->items, 'metadata' => $order->metadata, 'receipts' => $order->relationLoaded('receipts') ? $order->receipts->map(fn ($receipt): array => ['id' => (string) $receipt->getKey(), 'received_at' => $receipt->received_at?->toISOString(), 'received_by' => $receipt->received_by, 'items' => $receipt->items, 'notes' => $receipt->notes])->values()->all() : null]];
    }
}
