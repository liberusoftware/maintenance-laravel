<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Commercial\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Commercial\Actions\CreateCommercialRecord;
use Liberu\Modules\Maintenance\Commercial\Models\CommercialRecord;

class CommercialRecordController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $items = CommercialRecord::where('team_id', $teamId)->latest()->paginate(min($request->integer('per_page', 25), 100));

        return response()->json(['data' => $items->getCollection()->map(fn (CommercialRecord $record) => $this->resource($record))->values(), 'meta' => ['total' => $items->total()]]);
    }

    public function store(Request $request, CreateCommercialRecord $create): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('create', CommercialRecord::class), 403);
        $data = $request->validate(['kind' => 'required|string|max:255', 'title' => 'required|string|max:255', 'description' => 'nullable|string|max:10000', 'amount' => 'nullable|numeric', 'currency' => 'nullable|string|size:3', 'status' => 'nullable|string|max:40']);

        return response()->json(['data' => $this->resource($create->handle((int) $teamId, $data))], 201);
    }

    public function show(Request $request, CommercialRecord $record): JsonResponse
    {
        abort_unless((int) $request->user()?->currentTeam?->getKey() === (int) $record->team_id, 404);

        return response()->json(['data' => $this->resource($record)]);
    }

    private function resource(CommercialRecord $record): array
    {
        return ['id' => (string) $record->getKey(), 'type' => 'maintenance-commercial', 'attributes' => ['kind' => $record->kind, 'title' => $record->title, 'description' => $record->description, 'amount' => $record->amount, 'currency' => $record->currency, 'status' => $record->status]];
    }
}
