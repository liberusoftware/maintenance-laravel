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
        $items = PortalRecord::where('team_id', $teamId)->latest()->paginate(25);

        return response()->json(['data' => $items->getCollection()->values(), 'meta' => ['total' => $items->total()]]);
    }

    public function store(Request $request, CreatePortalRecord $create): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $data = $request->validate(['kind' => 'required|string|max:80', 'title' => 'required|string|max:255']);

        return response()->json(['data' => $create->handle((int) $teamId, $data)], 201);
    }

    public function show(Request $request, PortalRecord $record): JsonResponse
    {
        abort_unless((int) $request->user()?->currentTeam?->getKey() === (int) $record->team_id, 404);

        return response()->json(['data' => $record]);
    }
}
