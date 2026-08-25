<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Assets\Actions\CreateAsset;
use Liberu\Modules\Maintenance\Assets\Models\Asset;

class AssetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('viewAny', Asset::class), 403);
        $items = Asset::where('team_id', $teamId)->orderBy('name')->paginate(min($request->integer('per_page', 25), 100));

        return response()->json(['data' => $items->getCollection()->map(fn (Asset $a) => $this->resource($a))->values(), 'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()]]);
    }

    public function store(Request $request, CreateAsset $create): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('create', Asset::class), 403);
        $data = $request->validate(['name' => 'required|string|max:255', 'code' => 'required|string|max:64', 'category' => 'nullable|string|max:255', 'serial_number' => 'nullable|string|max:255', 'condition' => 'nullable|string|max:64', 'status' => 'nullable|string|max:64', 'qr_code' => 'nullable|string|max:255', 'barcode' => 'nullable|string|max:255', 'metadata' => 'nullable|array']);

        return response()->json(['data' => $this->resource($create->handle($teamId, $data))], 201);
    }

    public function show(Request $request, Asset $asset): JsonResponse
    {
        abort_unless($this->teamId($request) === $asset->team_id && $request->user()->can('view', $asset), 404);

        return response()->json(['data' => $this->resource($asset)]);
    }

    private function teamId(Request $request): ?int
    {
        $id = $request->user()?->currentTeam?->getKey();

        return $id === null ? null : (int) $id;
    }

    private function resource(Asset $a): array
    {
        return ['id' => (string) $a->getKey(), 'type' => 'maintenance-asset', 'attributes' => ['name' => $a->name, 'code' => $a->code, 'category' => $a->category, 'serial_number' => $a->serial_number, 'condition' => $a->condition, 'status' => $a->status, 'qr_code' => $a->qr_code, 'barcode' => $a->barcode, 'metadata' => $a->metadata, 'created_at' => $a->created_at?->toISOString(), 'updated_at' => $a->updated_at?->toISOString()]];
    }
}
