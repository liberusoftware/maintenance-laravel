<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Compliance\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Compliance\Actions\CreateComplianceRecord;
use Liberu\Modules\Maintenance\Compliance\Models\ComplianceRecord;

class ComplianceRecordController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('viewAny', ComplianceRecord::class), 403);
        $items = ComplianceRecord::where('team_id', $teamId)->latest()->paginate(min($request->integer('per_page', 25), 100));

        return response()->json(['data' => $items->getCollection()->map(fn (ComplianceRecord $record) => $this->resource($record))->values(), 'meta' => ['total' => $items->total()]]);
    }

    public function store(Request $request, CreateComplianceRecord $create): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('create', ComplianceRecord::class), 403);
        $data = $request->validate(['kind' => 'required|string|max:255', 'title' => 'required|string|max:255', 'description' => 'nullable|string|max:10000', 'status' => 'nullable|string|max:40', 'expires_at' => 'nullable|date']);

        return response()->json(['data' => $this->resource($create->handle((int) $teamId, $data))], 201);
    }

    public function show(Request $request, ComplianceRecord $record): JsonResponse
    {
        abort_unless((int) $request->user()?->currentTeam?->getKey() === (int) $record->team_id && $request->user()->can('view', $record), 404);

        return response()->json(['data' => $this->resource($record)]);
    }

    private function resource(ComplianceRecord $record): array
    {
        return ['id' => (string) $record->getKey(), 'type' => 'maintenance-compliance', 'attributes' => ['kind' => $record->kind, 'title' => $record->title, 'description' => $record->description, 'status' => $record->status, 'expires_at' => $record->expires_at]];
    }
}
