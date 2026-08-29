<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Inventory\Actions\AdjustStock;
use Liberu\Modules\Maintenance\Inventory\Actions\CreateStockItem;
use Liberu\Modules\Maintenance\Inventory\Actions\DeleteStockItem;
use Liberu\Modules\Maintenance\Inventory\Actions\IssueStock;
use Liberu\Modules\Maintenance\Inventory\Actions\ReleaseReservedStock;
use Liberu\Modules\Maintenance\Inventory\Actions\ReserveStock;
use Liberu\Modules\Maintenance\Inventory\Actions\ReturnStock;
use Liberu\Modules\Maintenance\Inventory\Actions\UpdateStockItem;
use Liberu\Modules\Maintenance\Inventory\Models\StockItem;

class StockItemController extends Controller
{
    public function index(Request $r): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($r->user()->can('viewAny', StockItem::class), 403);
        $query = StockItem::where('team_id', $id);
        $query = match ($r->string('stock')->toString()) {
            'low' => $query->lowStock(),
            'out' => $query->outOfStock(),
            default => $query,
        };
        $items = $query->orderBy('name')->paginate(min($r->integer('per_page', 25), 100));

        return response()->json(['data' => $items->getCollection()->map(fn (StockItem $i) => $this->resource($i))->values(), 'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()]]);
    }

    public function store(Request $r, CreateStockItem $create): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($r->user()->can('create', StockItem::class), 403);
        $data = $r->validate(['part_number' => 'required|string|max:96', 'name' => 'required|string|max:255', 'location' => 'nullable|string|max:255', 'quantity' => 'nullable|integer|min:0', 'reorder_level' => 'nullable|integer|min:0', 'unit' => 'nullable|string|max:32']);

        return response()->json(['data' => $this->resource($create->handle($id, $data))], 201);
    }

    public function show(Request $r, StockItem $stockItem): JsonResponse
    {
        abort_unless($this->teamId($r) === $stockItem->team_id && $r->user()->can('view', $stockItem), 404);

        return response()->json(['data' => $this->resource($stockItem)]);
    }

    public function adjust(Request $r, StockItem $stockItem, AdjustStock $adjust): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $stockItem->team_id && $r->user()->can('update', $stockItem), 404);
        $data = $r->validate(['delta' => ['required', 'integer', 'between:-1000000,1000000'], 'reason' => ['sometimes', 'string', 'max:64'], 'notes' => ['sometimes', 'nullable', 'string', 'max:10000']]);

        return response()->json(['data' => $this->resource($adjust->handle($id, $stockItem, $data['delta'], $data['reason'] ?? 'adjustment', $r->user()?->getAuthIdentifier(), $data['notes'] ?? null))]);
    }

    public function reserve(Request $r, StockItem $stockItem, ReserveStock $reserve): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $stockItem->team_id && $r->user()->can('update', $stockItem), 404);
        $data = $r->validate(['quantity' => ['required', 'integer', 'min:1', 'max:1000000']]);

        return response()->json(['data' => $this->resource($reserve->handle($id, $stockItem, $data['quantity']))]);
    }

    public function release(Request $r, StockItem $stockItem, ReleaseReservedStock $release): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $stockItem->team_id && $r->user()->can('update', $stockItem), 404);
        $data = $r->validate(['quantity' => ['required', 'integer', 'min:1', 'max:1000000']]);

        return response()->json(['data' => $this->resource($release->handle($id, $stockItem, $data['quantity']))]);
    }

    public function issue(Request $r, StockItem $stockItem, IssueStock $issue): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $stockItem->team_id && $r->user()->can('update', $stockItem), 404);
        $data = $r->validate(['quantity' => ['required', 'integer', 'min:1', 'max:1000000'], 'notes' => ['sometimes', 'nullable', 'string', 'max:10000']]);

        return response()->json(['data' => $this->resource($issue->handle($id, $stockItem, $data['quantity'], $r->user()?->getAuthIdentifier(), $data['notes'] ?? null))]);
    }

    public function return(Request $r, StockItem $stockItem, ReturnStock $returnStock): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $stockItem->team_id && $r->user()->can('update', $stockItem), 404);
        $data = $r->validate(['quantity' => ['required', 'integer', 'min:1', 'max:1000000'], 'notes' => ['sometimes', 'nullable', 'string', 'max:10000']]);

        return response()->json(['data' => $this->resource($returnStock->handle($id, $stockItem, $data['quantity'], $r->user()?->getAuthIdentifier(), $data['notes'] ?? null))]);
    }

    public function update(Request $r, StockItem $stockItem, UpdateStockItem $update): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $stockItem->team_id && $r->user()->can('update', $stockItem), 404);
        $data = $r->validate(['part_number' => 'sometimes|required|string|max:96', 'name' => 'sometimes|required|string|max:255', 'location' => 'sometimes|nullable|string|max:255', 'reorder_level' => 'sometimes|integer|min:0', 'unit' => 'sometimes|nullable|string|max:32']);

        return response()->json(['data' => $this->resource($update->handle($id, $stockItem, $data))]);
    }

    public function destroy(Request $r, StockItem $stockItem, DeleteStockItem $delete): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $stockItem->team_id && $r->user()->can('delete', $stockItem), 404);
        $delete->handle($id, $stockItem);

        return response()->json(null, 204);
    }

    private function teamId(Request $r): ?int
    {
        $id = $r->user()?->currentTeam?->getKey();

        return $id === null ? null : (int) $id;
    }

    private function resource(StockItem $i): array
    {
        return ['id' => (string) $i->getKey(), 'type' => 'maintenance-stock-item', 'attributes' => ['part_number' => $i->part_number, 'name' => $i->name, 'location' => $i->location, 'quantity' => $i->quantity, 'reserved_quantity' => $i->reserved_quantity, 'available_quantity' => $i->availableQuantity(), 'reorder_level' => $i->reorder_level, 'unit' => $i->unit]];
    }

    public function movements(Request $r, StockItem $stockItem): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $stockItem->team_id && $r->user()->can('view', $stockItem), 404);

        return response()->json(['data' => $stockItem->movements()->latest()->get()->map(fn ($movement): array => ['id' => (string) $movement->id, 'type' => 'maintenance-stock-movement', 'attributes' => $movement->only(['delta', 'quantity_before', 'quantity_after', 'reason', 'notes', 'user_id', 'created_at'])])->values()]);
    }
}
