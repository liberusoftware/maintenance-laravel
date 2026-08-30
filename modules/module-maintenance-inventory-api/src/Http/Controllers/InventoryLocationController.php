<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Inventory\Actions\CreateInventoryLocation;
use Liberu\Modules\Maintenance\Inventory\Actions\SetStockLevel;
use Liberu\Modules\Maintenance\Inventory\Actions\TransferStock;
use Liberu\Modules\Maintenance\Inventory\Models\InventoryLocation;
use Liberu\Modules\Maintenance\Inventory\Models\StockItem;

final class InventoryLocationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);

        return response()->json(['data' => InventoryLocation::query()->where('team_id', $teamId)->orderBy('name')->get()->map(fn (InventoryLocation $location): array => $this->resource($location))->values()]);
    }

    public function store(Request $request, CreateInventoryLocation $create): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        $data = $request->validate(['code' => ['required', 'string', 'max:64'], 'name' => ['required', 'string', 'max:255'], 'type' => ['nullable', 'string', 'in:warehouse,van,other'], 'is_active' => ['nullable', 'boolean']]);

        return response()->json(['data' => $this->resource($create->handle($teamId, $data))], 201);
    }

    public function setLevel(Request $request, string $location, SetStockLevel $setLevel): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        $locationModel = $this->location($teamId, $location);
        $data = $request->validate(['stock_item_id' => ['required', 'integer'], 'quantity' => ['required', 'integer', 'min:0']]);
        $item = StockItem::query()->where('team_id', $teamId)->findOrFail($data['stock_item_id']);

        return response()->json(['data' => $this->levelResource($setLevel->handle($teamId, $item, $locationModel, $data['quantity'], (int) $request->user()->getKey()))], 201);
    }

    public function transfer(Request $request, TransferStock $transfer): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        $data = $request->validate(['stock_item_id' => ['required', 'integer'], 'from_location_id' => ['required', 'integer'], 'to_location_id' => ['required', 'integer'], 'quantity' => ['required', 'integer', 'min:1'], 'notes' => ['nullable', 'string', 'max:10000']]);
        $item = StockItem::query()->where('team_id', $teamId)->findOrFail($data['stock_item_id']);
        $from = InventoryLocation::query()->where('team_id', $teamId)->findOrFail($data['from_location_id']);
        $to = InventoryLocation::query()->where('team_id', $teamId)->findOrFail($data['to_location_id']);
        $transfer->handle($teamId, $item, $from, $to, $data['quantity'], (int) $request->user()->getKey(), $data['notes'] ?? null);

        return response()->json(['data' => ['stock_item_id' => $item->getKey(), 'from_location_id' => $from->getKey(), 'to_location_id' => $to->getKey(), 'quantity' => $data['quantity']]]);
    }

    private function teamId(Request $request): ?int
    {
        return $request->user()?->currentTeam?->getKey() === null ? null : (int) $request->user()->currentTeam->getKey();
    }

    private function location(int $teamId, string $id): InventoryLocation
    {
        return InventoryLocation::query()->where('team_id', $teamId)->findOrFail($id);
    }

    private function resource(InventoryLocation $location): array
    {
        return ['id' => (string) $location->getKey(), 'type' => 'maintenance-inventory-location', 'attributes' => ['code' => $location->code, 'name' => $location->name, 'type' => $location->type, 'is_active' => $location->is_active]];
    }

    private function levelResource(object $level): array
    {
        return ['id' => (string) $level->getKey(), 'type' => 'maintenance-stock-level', 'attributes' => ['stock_item_id' => $level->stock_item_id, 'location_id' => $level->location_id, 'quantity' => $level->quantity, 'reserved_quantity' => $level->reserved_quantity, 'available_quantity' => $level->availableQuantity()]];
    }
}
