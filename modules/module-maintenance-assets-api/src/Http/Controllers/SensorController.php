<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Liberu\Modules\Maintenance\Assets\Actions\RecordSensorReading;
use Liberu\Modules\Maintenance\Assets\Models\Asset;
use Liberu\Modules\Maintenance\Assets\Services\SensorHealthService;

final class SensorController extends Controller
{
    public function store(Request $request, RecordSensorReading $record): JsonResponse
    {
        $data = $request->validate($this->rules());
        $asset = Asset::query()->where('sensor_id', $data['sensor_id'])->firstOrFail();

        if (! $asset->sensor_enabled) {
            return response()->json(['success' => false, 'message' => 'Sensor is not enabled for this asset.'], 403);
        }

        return response()->json(['success' => true, 'data' => $record->handle($asset, $data), 'message' => 'Sensor reading stored successfully'], 201);
    }

    public function batch(Request $request, RecordSensorReading $record): JsonResponse
    {
        $request->validate(['readings' => ['required', 'array', 'min:1'], 'readings.*.sensor_id' => ['required', 'string', Rule::exists('maintenance_assets', 'sensor_id')], 'readings.*.metric_name' => ['required', 'string', 'max:120'], 'readings.*.value' => ['required', 'numeric'], 'readings.*.sensor_type' => ['nullable', 'string', 'max:80'], 'readings.*.unit' => ['nullable', 'string', 'max:32'], 'readings.*.metadata' => ['nullable', 'array'], 'readings.*.reading_at' => ['nullable', 'date']]);
        $stored = 0;
        $errors = [];

        foreach ($request->array('readings') as $index => $data) {
            $asset = Asset::query()->where('sensor_id', $data['sensor_id'])->first();
            if ($asset === null || ! $asset->sensor_enabled) {
                $errors[] = ['index' => $index, 'sensor_id' => $data['sensor_id'], 'message' => 'Sensor is not enabled for this asset.'];

                continue;
            }
            try {
                $record->handle($asset, $data);
                $stored++;
            } catch (\Throwable $exception) {
                $errors[] = ['index' => $index, 'sensor_id' => $data['sensor_id'], 'message' => $exception->getMessage()];
            }
        }

        return response()->json(['success' => true, 'stored_count' => $stored, 'errors_count' => count($errors), 'errors' => $errors]);
    }

    public function health(Request $request, Asset $asset, SensorHealthService $health): JsonResponse
    {
        $this->authorizeAsset($request, $asset);

        return response()->json(['success' => true, 'data' => $health->summary($asset, max(1, $request->integer('hours', 24)))]);
    }

    public function insights(Request $request, Asset $asset, SensorHealthService $health): JsonResponse
    {
        $this->authorizeAsset($request, $asset);

        return response()->json(['success' => true, 'data' => $health->insights($asset, max(1, $request->integer('days', 30)))]);
    }

    public function dashboard(Request $request, SensorHealthService $health): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return response()->json(['success' => true, 'data' => $health->dashboard((int) $teamId)]);
    }

    private function authorizeAsset(Request $request, Asset $asset): void
    {
        abort_unless($request->user()?->currentTeam?->getKey() === $asset->team_id && $request->user()->can('view', $asset), 404);
    }

    private function rules(): array
    {
        return ['sensor_id' => ['required', 'string', Rule::exists('maintenance_assets', 'sensor_id')], 'sensor_type' => ['nullable', 'string', 'max:80'], 'metric_name' => ['required', 'string', 'max:120'], 'value' => ['required', 'numeric'], 'unit' => ['nullable', 'string', 'max:32'], 'metadata' => ['nullable', 'array'], 'reading_at' => ['nullable', 'date']];
    }
}
