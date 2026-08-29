<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Assets\Actions\CreateAsset;
use Liberu\Modules\Maintenance\Assets\Actions\DeleteAsset;
use Liberu\Modules\Maintenance\Assets\Actions\UpdateAsset;
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

    public function update(Request $request, Asset $asset, UpdateAsset $update): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($teamId === (int) $asset->team_id && $request->user()->can('update', $asset), 404);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['sometimes', 'required', 'string', 'max:64'],
            'category' => ['sometimes', 'nullable', 'string', 'max:255'],
            'serial_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'condition' => ['sometimes', 'nullable', 'string', 'max:64'],
            'status' => ['sometimes', 'nullable', 'string', 'max:64'],
            'qr_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:255'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ]);

        return response()->json(['data' => $this->resource($update->handle($teamId, $asset, $data))]);
    }

    public function destroy(Request $request, Asset $asset, DeleteAsset $delete): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($teamId === (int) $asset->team_id && $request->user()->can('delete', $asset), 404);
        $delete->handle($teamId, $asset);

        return response()->json(null, 204);
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
