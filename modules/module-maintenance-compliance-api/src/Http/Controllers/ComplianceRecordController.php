<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Compliance\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Compliance\Actions\CreateComplianceRecord;
use Liberu\Modules\Maintenance\Compliance\Actions\DeleteComplianceRecord;
use Liberu\Modules\Maintenance\Compliance\Actions\UpdateComplianceRecord;
use Liberu\Modules\Maintenance\Compliance\Models\ComplianceRecord;

class ComplianceRecordController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('viewAny', ComplianceRecord::class), 403);
        $query = ComplianceRecord::where('team_id', $teamId);
        if ($request->filled('kind')) {
            $query->where('kind', $request->string('kind')->toString());
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->boolean('expired')) {
            $query->expired();
        } elseif ($request->boolean('current')) {
            $query->current();
        }
        $items = $query->latest()->paginate(min($request->integer('per_page', 25), 100));

        return response()->json(['data' => $items->getCollection()->map(fn (ComplianceRecord $record) => $this->resource($record))->values(), 'meta' => ['total' => $items->total()]]);
    }

    public function store(Request $request, CreateComplianceRecord $create): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('create', ComplianceRecord::class), 403);
        $data = $request->validate(['kind' => 'required|string|max:255', 'title' => 'required|string|max:255', 'description' => 'nullable|string|max:10000', 'status' => 'nullable|string|max:40', 'expires_at' => 'nullable|date', 'metadata' => 'nullable|array']);

        return response()->json(['data' => $this->resource($create->handle((int) $teamId, $data))], 201);
    }

    public function show(Request $request, ComplianceRecord $record): JsonResponse
    {
        abort_unless((int) $request->user()?->currentTeam?->getKey() === (int) $record->team_id && $request->user()->can('view', $record), 404);

        return response()->json(['data' => $this->resource($record)]);
    }

    public function update(Request $request, ComplianceRecord $record, UpdateComplianceRecord $update): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        abort_unless((int) $teamId === (int) $record->team_id && $request->user()->can('update', $record), 404);
        $data = $request->validate(['kind' => 'sometimes|required|string|max:255', 'title' => 'sometimes|required|string|max:255', 'description' => 'sometimes|nullable|string|max:10000', 'status' => 'sometimes|string|max:40', 'expires_at' => 'sometimes|nullable|date', 'metadata' => 'sometimes|nullable|array']);

        return response()->json(['data' => $this->resource($update->handle((int) $teamId, $record, $data))]);
    }

    public function destroy(Request $request, ComplianceRecord $record, DeleteComplianceRecord $delete): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        abort_unless((int) $teamId === (int) $record->team_id && $request->user()->can('delete', $record), 404);
        $delete->handle((int) $teamId, $record);

        return response()->json(null, 204);
    }

    private function resource(ComplianceRecord $record): array
    {
        return ['id' => (string) $record->getKey(), 'type' => 'maintenance-compliance', 'attributes' => ['kind' => $record->kind, 'title' => $record->title, 'description' => $record->description, 'status' => $record->status, 'expires_at' => $record->expires_at?->toISOString(), 'metadata' => $record->metadata]];
    }
}
