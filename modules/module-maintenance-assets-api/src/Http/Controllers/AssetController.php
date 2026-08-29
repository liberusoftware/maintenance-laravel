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
        $query = Asset::where('team_id', $teamId);
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->trim()->toString());
        }
        if ($request->filled('criticality')) {
            $query->where('criticality', $request->string('criticality')->trim()->toString());
        }
        if ($request->has('sensor_enabled')) {
            $query->where('sensor_enabled', $request->boolean('sensor_enabled'));
        }
        if ($request->boolean('critical_readings')) {
            $query->withCriticalReadings();
        }
        $items = $query->orderBy('name')->paginate(min($request->integer('per_page', 25), 100));

        return response()->json(['data' => $items->getCollection()->map(fn (Asset $a) => $this->resource($a))->values(), 'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()]]);
    }

    public function store(Request $request, CreateAsset $create): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('create', Asset::class), 403);
        $data = $request->validate(['name' => 'required|string|max:255', 'code' => 'required|string|max:64', 'category' => 'nullable|string|max:255', 'serial_number' => 'nullable|string|max:255', 'condition' => 'nullable|string|max:64', 'criticality' => 'nullable|in:normal,high,critical', 'status' => 'nullable|string|max:64', 'qr_code' => 'nullable|string|max:255', 'barcode' => 'nullable|string|max:255', 'sensor_enabled' => 'sometimes|boolean', 'sensor_type' => 'nullable|string|max:80', 'sensor_id' => 'nullable|string|max:255', 'sensor_config' => 'nullable|array', 'last_sensor_reading_at' => 'nullable|date', 'metadata' => 'nullable|array']);

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
            'criticality' => ['sometimes', 'nullable', 'in:normal,high,critical'],
            'status' => ['sometimes', 'nullable', 'string', 'max:64'],
            'qr_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:255'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'sensor_enabled' => ['sometimes', 'boolean'],
            'sensor_type' => ['sometimes', 'nullable', 'string', 'max:80'],
            'sensor_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sensor_config' => ['sometimes', 'nullable', 'array'],
            'last_sensor_reading_at' => ['sometimes', 'nullable', 'date'],
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
        return ['id' => (string) $a->getKey(), 'type' => 'maintenance-asset', 'attributes' => ['name' => $a->name, 'code' => $a->code, 'category' => $a->category, 'serial_number' => $a->serial_number, 'condition' => $a->condition, 'criticality' => $a->criticality, 'status' => $a->status, 'health_status' => $a->health_status, 'sensor_enabled' => $a->sensor_enabled, 'sensor_type' => $a->sensor_type, 'sensor_id' => $a->sensor_id, 'sensor_config' => $a->sensor_config, 'last_sensor_reading_at' => $a->last_sensor_reading_at?->toISOString(), 'qr_code' => $a->qr_code, 'barcode' => $a->barcode, 'metadata' => $a->metadata, 'created_at' => $a->created_at?->toISOString(), 'updated_at' => $a->updated_at?->toISOString()]];
    }
}
