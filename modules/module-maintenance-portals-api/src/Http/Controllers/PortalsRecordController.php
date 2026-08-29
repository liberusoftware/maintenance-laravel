<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Portals\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Portal\Actions\CreatePortalRecord;
use Liberu\Modules\Maintenance\Portal\Models\PortalRecord;

class PortalsRecordController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('viewAny', PortalRecord::class), 403);
        $items = PortalRecord::where('team_id', $teamId)->latest()->paginate(min($request->integer('per_page', 25), 100));

        return response()->json(['data' => $items->getCollection()->map(fn (PortalRecord $record) => $this->resource($record))->values(), 'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()]]);
    }

    public function store(Request $request, CreatePortalRecord $create): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('create', PortalRecord::class), 403);
        $data = $request->validate(['kind' => 'required|string|max:80', 'title' => 'required|string|max:255']);

        return response()->json(['data' => $this->resource($create->handle((int) $teamId, $data))], 201);
    }

    public function show(Request $request, PortalRecord $record): JsonResponse
    {
        abort_unless((int) $request->user()?->currentTeam?->getKey() === (int) $record->team_id && $request->user()->can('view', $record), 404);

        return response()->json(['data' => $this->resource($record)]);
    }

    private function resource(PortalRecord $record): array
    {
        return ['id' => (string) $record->getKey(), 'type' => 'maintenance-portal', 'attributes' => ['kind' => $record->kind, 'title' => $record->title, 'description' => $record->description, 'status' => $record->status, 'requested_by' => $record->requested_by, 'metadata' => $record->metadata, 'created_at' => $record->created_at?->toISOString(), 'updated_at' => $record->updated_at?->toISOString()]];
    }
}
