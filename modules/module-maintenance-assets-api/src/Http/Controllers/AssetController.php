<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Assets\Actions\CreateAsset;
use Liberu\Modules\Maintenance\Assets\Actions\CreateAssetCategory;
use Liberu\Modules\Maintenance\Assets\Actions\CreateAssetMeter;
use Liberu\Modules\Maintenance\Assets\Actions\CreateAssetSpecification;
use Liberu\Modules\Maintenance\Assets\Actions\CreateAssetWarranty;
use Liberu\Modules\Maintenance\Assets\Actions\DeleteAsset;
use Liberu\Modules\Maintenance\Assets\Actions\RecordAssetMeterReading;
use Liberu\Modules\Maintenance\Assets\Actions\RecordAssetHistory;
use Liberu\Modules\Maintenance\Assets\Actions\UpdateAsset;
use Liberu\Modules\Maintenance\Assets\Models\Asset;
use Liberu\Modules\Maintenance\Assets\Models\AssetCategory;
use Liberu\Modules\Maintenance\Assets\Models\AssetHistory;
use Liberu\Modules\Maintenance\Assets\Models\AssetMeter;
use Liberu\Modules\Maintenance\Assets\Models\AssetMeterReading;
use Liberu\Modules\Maintenance\Assets\Models\AssetSpecification;
use Liberu\Modules\Maintenance\Assets\Models\AssetWarranty;

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
        if ($request->filled('condition')) {
            $query->inCondition($request->string('condition')->trim()->toString());
        }
        if ($request->has('sensor_enabled')) {
            $query->where('sensor_enabled', $request->boolean('sensor_enabled'));
        }
        if ($request->boolean('critical_readings')) {
            $query->withCriticalReadings();
        }
        if ($request->boolean('under_warranty')) {
            $query->underWarranty();
        } elseif ($request->boolean('warranty_expired')) {
            $query->warrantyExpired();
        }
        $items = $query->orderBy('name')->paginate(min($request->integer('per_page', 25), 100));

        return response()->json(['data' => $items->getCollection()->map(fn (Asset $a) => $this->resource($a))->values(), 'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()]]);
    }

    public function store(Request $request, CreateAsset $create): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('create', Asset::class), 403);
        $data = $request->validate(['name' => 'required|string|max:255', 'parent_id' => 'nullable|integer', 'category_id' => 'nullable|integer', 'description' => 'nullable|string|max:10000', 'code' => 'required|string|max:64', 'category' => 'nullable|string|max:255', 'serial_number' => 'nullable|string|max:255', 'model' => 'nullable|string|max:255', 'manufacturer' => 'nullable|string|max:255', 'location' => 'nullable|string|max:255', 'purchase_date' => 'nullable|date', 'warranty_expiry' => 'nullable|date|after_or_equal:purchase_date', 'notes' => 'nullable|string|max:10000', 'condition' => 'nullable|string|max:64', 'criticality' => 'nullable|in:normal,high,critical', 'status' => 'nullable|string|max:64', 'qr_code' => 'nullable|string|max:255', 'barcode' => 'nullable|string|max:255', 'sensor_enabled' => 'sometimes|boolean', 'sensor_type' => 'nullable|string|max:80', 'sensor_id' => 'nullable|string|max:255', 'sensor_config' => 'nullable|array', 'last_sensor_reading_at' => 'nullable|date', 'metadata' => 'nullable|array']);

        return response()->json(['data' => $this->resource($create->handle($teamId, $data))], 201);
    }

    public function categories(Request $request): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('viewAny', Asset::class), 403);

        return response()->json(['data' => AssetCategory::query()->where('team_id', $teamId)->active()->with('children')->orderBy('name')->get()->map(fn (AssetCategory $category): array => $this->categoryResource($category))->values()]);
    }

    public function storeCategory(Request $request, CreateAssetCategory $create): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('create', Asset::class), 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'code' => ['required', 'string', 'max:64'], 'parent_id' => ['nullable', 'integer'], 'description' => ['nullable', 'string', 'max:10000'], 'is_active' => ['sometimes', 'boolean']]);

        return response()->json(['data' => $this->categoryResource($create->handle($teamId, $data))], 201);
    }

    public function show(Request $request, Asset $asset): JsonResponse
    {
        abort_unless($this->teamId($request) === $asset->team_id && $request->user()->can('view', $asset), 404);

        return response()->json(['data' => $this->resource($asset)]);
    }

    public function history(Request $request, string $asset, RecordAssetHistory $recordHistory): JsonResponse
    {
        $assetRecord = $this->assetForCurrentTeam($request, $asset);
        $teamId = $this->authorizeAsset($request, $assetRecord, 'update');
        $data = $request->validate(['type' => ['required', 'string', 'max:64'], 'note' => ['required', 'string', 'max:10000']]);

        return response()->json(['data' => $this->resource($recordHistory->handle($teamId, $assetRecord, $data['type'], $data['note'], (int) $request->user()->getKey()))]);
    }

    public function specifications(Request $request, string $asset): JsonResponse
    {
        $assetRecord = $this->assetForCurrentTeam($request, $asset);
        $this->authorizeAsset($request, $assetRecord, 'view');

        return response()->json(['data' => $assetRecord->specifications()->get()->map(fn (AssetSpecification $specification): array => $this->specificationResource($specification))->values()]);
    }

    public function storeSpecification(Request $request, string $asset, CreateAssetSpecification $create): JsonResponse
    {
        $assetRecord = $this->assetForCurrentTeam($request, $asset);
        $teamId = $this->authorizeAsset($request, $assetRecord, 'update');
        $data = $request->validate(['key' => ['required', 'string', 'max:128'], 'value' => ['required', 'string', 'max:10000'], 'unit' => ['nullable', 'string', 'max:32'], 'sort_order' => ['sometimes', 'integer', 'min:0']]);

        return response()->json(['data' => $this->specificationResource($create->handle($teamId, $assetRecord, $data))], 201);
    }

    public function warranties(Request $request, string $asset): JsonResponse
    {
        $assetRecord = $this->assetForCurrentTeam($request, $asset);
        $this->authorizeAsset($request, $assetRecord, 'view');

        return response()->json(['data' => $assetRecord->warranties()->get()->map(fn (AssetWarranty $warranty): array => $this->warrantyResource($warranty))->values()]);
    }

    public function storeWarranty(Request $request, string $asset, CreateAssetWarranty $create): JsonResponse
    {
        $assetRecord = $this->assetForCurrentTeam($request, $asset);
        $teamId = $this->authorizeAsset($request, $assetRecord, 'update');
        $data = $request->validate(['provider' => ['nullable', 'string', 'max:255'], 'reference' => ['nullable', 'string', 'max:255'], 'starts_on' => ['nullable', 'date'], 'expires_on' => ['required', 'date'], 'terms' => ['nullable', 'string', 'max:10000'], 'status' => ['sometimes', 'in:active,expired,void']]);

        return response()->json(['data' => $this->warrantyResource($create->handle($teamId, $assetRecord, $data))], 201);
    }

    public function assetHistory(Request $request, string $asset): JsonResponse
    {
        $assetRecord = $this->assetForCurrentTeam($request, $asset);
        $this->authorizeAsset($request, $assetRecord, 'view');

        return response()->json(['data' => $assetRecord->history()->get()->map(fn (AssetHistory $entry): array => $this->historyResource($entry))->values()]);
    }

    public function update(Request $request, Asset $asset, UpdateAsset $update): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($teamId === (int) $asset->team_id && $request->user()->can('update', $asset), 404);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'code' => ['sometimes', 'required', 'string', 'max:64'],
            'category' => ['sometimes', 'nullable', 'string', 'max:255'],
            'serial_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'model' => ['sometimes', 'nullable', 'string', 'max:255'],
            'manufacturer' => ['sometimes', 'nullable', 'string', 'max:255'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'purchase_date' => ['sometimes', 'nullable', 'date'],
            'warranty_expiry' => ['sometimes', 'nullable', 'date', 'after_or_equal:purchase_date'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:10000'],
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

    public function meters(Request $request, string $asset): JsonResponse
    {
        $asset = $this->assetForCurrentTeam($request, $asset);
        $this->authorizeAsset($request, $asset, 'view');

        return response()->json(['data' => $asset->meters()->active()->orderBy('name')->get()->map(fn (AssetMeter $meter): array => $this->meterResource($meter))->values()]);
    }

    public function storeMeter(Request $request, string $asset, CreateAssetMeter $create): JsonResponse
    {
        $asset = $this->assetForCurrentTeam($request, $asset);
        $teamId = $this->authorizeAsset($request, $asset, 'update');
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'unit' => ['required', 'string', 'max:32'], 'is_active' => ['sometimes', 'boolean']]);

        return response()->json(['data' => $this->meterResource($create->handle($teamId, $asset, $data))], 201);
    }

    public function storeMeterReading(Request $request, string $asset, string $meter, RecordAssetMeterReading $record): JsonResponse
    {
        $asset = $this->assetForCurrentTeam($request, $asset);
        $teamId = $this->authorizeAsset($request, $asset, 'update');
        $meterRecord = AssetMeter::query()->where('team_id', $teamId)->where('asset_id', $asset->getKey())->findOrFail($meter);
        $data = $request->validate(['value' => ['required', 'numeric'], 'recorded_at' => ['nullable', 'date'], 'notes' => ['nullable', 'string', 'max:10000']]);
        $reading = $record->handle($teamId, $asset, $meterRecord, (float) $data['value'], $data['recorded_at'] ?? null, (int) $request->user()->getKey(), $data['notes'] ?? null);

        return response()->json(['data' => $this->meterReadingResource($reading)], 201);
    }

    private function teamId(Request $request): ?int
    {
        $id = $request->user()?->currentTeam?->getKey();

        return $id === null ? null : (int) $id;
    }

    private function authorizeAsset(Request $request, Asset $asset, string $ability): int
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($teamId === (int) $asset->team_id && $request->user()->can($ability, $asset), 404);

        return $teamId;
    }

    private function assetForCurrentTeam(Request $request, string $key): Asset
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);

        return Asset::query()->where('team_id', $teamId)->findOrFail($key);
    }

    private function resource(Asset $a): array
    {
        return ['id' => (string) $a->getKey(), 'type' => 'maintenance-asset', 'attributes' => ['name' => $a->name, 'parent_id' => $a->parent_id, 'category_id' => $a->category_id, 'description' => $a->description, 'code' => $a->code, 'category' => $a->category, 'serial_number' => $a->serial_number, 'model' => $a->model, 'manufacturer' => $a->manufacturer, 'location' => $a->location, 'purchase_date' => $a->purchase_date?->toDateString(), 'warranty_expiry' => $a->warranty_expiry?->toDateString(), 'warranty_days_remaining' => $a->warrantyDaysRemaining(), 'is_under_warranty' => $a->isUnderWarranty(), 'notes' => $a->notes, 'condition' => $a->condition, 'criticality' => $a->criticality, 'status' => $a->status, 'health_status' => $a->health_status, 'sensor_enabled' => $a->sensor_enabled, 'sensor_type' => $a->sensor_type, 'sensor_id' => $a->sensor_id, 'sensor_config' => $a->sensor_config, 'last_sensor_reading_at' => $a->last_sensor_reading_at?->toISOString(), 'qr_code' => $a->qr_code, 'barcode' => $a->barcode, 'metadata' => $a->metadata, 'created_at' => $a->created_at?->toISOString(), 'updated_at' => $a->updated_at?->toISOString()]];
    }

    private function meterResource(AssetMeter $meter): array
    {
        return ['id' => (string) $meter->getKey(), 'type' => 'maintenance-asset-meter', 'attributes' => ['asset_id' => $meter->asset_id, 'name' => $meter->name, 'unit' => $meter->unit, 'current_value' => $meter->current_value, 'last_reading_at' => $meter->last_reading_at?->toISOString(), 'is_active' => $meter->is_active]];
    }

    private function meterReadingResource(AssetMeterReading $reading): array
    {
        return ['id' => (string) $reading->getKey(), 'type' => 'maintenance-asset-meter-reading', 'attributes' => ['asset_id' => $reading->asset_id, 'meter_id' => $reading->meter_id, 'value' => $reading->value, 'recorded_at' => $reading->recorded_at?->toISOString(), 'recorded_by' => $reading->recorded_by, 'notes' => $reading->notes]];
    }

    private function categoryResource(AssetCategory $category): array
    {
        return ['id' => (string) $category->getKey(), 'type' => 'maintenance-asset-category', 'attributes' => ['name' => $category->name, 'code' => $category->code, 'description' => $category->description, 'parent_id' => $category->parent_id, 'is_active' => $category->is_active]];
    }

    private function specificationResource(AssetSpecification $specification): array
    {
        return ['id' => (string) $specification->getKey(), 'type' => 'maintenance-asset-specification', 'attributes' => ['asset_id' => $specification->asset_id, 'key' => $specification->key, 'value' => $specification->value, 'unit' => $specification->unit, 'sort_order' => $specification->sort_order]];
    }

    private function warrantyResource(AssetWarranty $warranty): array
    {
        return ['id' => (string) $warranty->getKey(), 'type' => 'maintenance-asset-warranty', 'attributes' => ['asset_id' => $warranty->asset_id, 'provider' => $warranty->provider, 'reference' => $warranty->reference, 'starts_on' => $warranty->starts_on?->toDateString(), 'expires_on' => $warranty->expires_on?->toDateString(), 'terms' => $warranty->terms, 'status' => $warranty->status]];
    }

    private function historyResource(AssetHistory $entry): array
    {
        return ['id' => (string) $entry->getKey(), 'type' => 'maintenance-asset-history', 'attributes' => ['asset_id' => $entry->asset_id, 'actor_id' => $entry->actor_id, 'type' => $entry->type, 'note' => $entry->note, 'metadata' => $entry->metadata, 'occurred_at' => $entry->occurred_at?->toISOString()]];
    }
}
