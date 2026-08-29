<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Inventory\Actions\AdjustStock;
use Liberu\Modules\Maintenance\Inventory\Actions\CreateStockItem;
use Liberu\Modules\Maintenance\Inventory\Models\StockItem;

class StockItemController extends Controller
{
    public function index(Request $r): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($r->user()->can('viewAny', StockItem::class), 403);
        $items = StockItem::where('team_id', $id)->orderBy('name')->paginate(min($r->integer('per_page', 25), 100));

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
        $data = $r->validate(['delta' => ['required', 'integer', 'between:-1000000,1000000']]);

        return response()->json(['data' => $this->resource($adjust->handle($id, $stockItem, $data['delta']))]);
    }

    private function teamId(Request $r): ?int
    {
        $id = $r->user()?->currentTeam?->getKey();

        return $id === null ? null : (int) $id;
    }

    private function resource(StockItem $i): array
    {
        return ['id' => (string) $i->getKey(), 'type' => 'maintenance-stock-item', 'attributes' => ['part_number' => $i->part_number, 'name' => $i->name, 'location' => $i->location, 'quantity' => $i->quantity, 'reorder_level' => $i->reorder_level, 'unit' => $i->unit]];
    }
}
