<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Notes\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Notes\Actions\CreateMaintenanceNote;
use Liberu\Modules\Maintenance\Notes\Models\MaintenanceNote;

final class MaintenanceNoteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_unless($request->user()->can('viewAny', MaintenanceNote::class), 403);
        $query = MaintenanceNote::query()->where('team_id', $teamId);
        if ($request->filled('noteable_type')) $query->where('noteable_type', $request->string('noteable_type')->toString());
        if ($request->filled('noteable_id')) $query->where('noteable_id', $request->integer('noteable_id'));
        $notes = $query->latest()->paginate(min($request->integer('per_page', 25), 100));
        return response()->json(['data' => $notes->getCollection()->map(fn (MaintenanceNote $note): array => $this->resource($note))->values(), 'meta' => ['total' => $notes->total()]]);
    }

    public function store(Request $request, CreateMaintenanceNote $create): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_unless($request->user()->can('create', MaintenanceNote::class), 403);
        $data = $request->validate(['content' => ['required', 'string', 'max:10000'], 'noteable_type' => ['nullable', 'string', 'max:255'], 'noteable_id' => ['nullable', 'integer', 'min:1']]);
        $data['created_by'] = (int) $request->user()->getKey();
        return response()->json(['data' => $this->resource($create->handle($teamId, $data))], 201);
    }

    public function show(Request $request, string $note): JsonResponse
    {
        $note = $this->note($request, $note);
        abort_unless($request->user()->can('view', $note), 404);
        return response()->json(['data' => $this->resource($note)]);
    }

    public function update(Request $request, string $note): JsonResponse
    {
        $note = $this->note($request, $note);
        abort_unless($request->user()->can('update', $note), 404);
        $note->update(array_merge($request->validate(['content' => ['sometimes', 'required', 'string', 'max:10000'], 'noteable_type' => ['sometimes', 'nullable', 'string', 'max:255'], 'noteable_id' => ['sometimes', 'nullable', 'integer', 'min:1']]), ['updated_by' => (int) $request->user()->getKey()]));
        return response()->json(['data' => $this->resource($note->refresh())]);
    }

    public function destroy(Request $request, string $note): JsonResponse
    {
        $note = $this->note($request, $note);
        abort_unless($request->user()->can('delete', $note), 404);
        $note->delete();
        return response()->json(null, 204);
    }

    private function teamId(Request $request): int { $id = $request->user()?->currentTeam?->getKey(); abort_if($id === null, 403); return (int) $id; }
    private function note(Request $request, string $key): MaintenanceNote { return MaintenanceNote::query()->where('team_id', $this->teamId($request))->findOrFail($key); }
    private function resource(MaintenanceNote $note): array { return ['id' => (string) $note->getKey(), 'type' => 'maintenance-note', 'attributes' => ['content' => $note->content, 'noteable_type' => $note->noteable_type, 'noteable_id' => $note->noteable_id, 'created_by' => $note->created_by, 'updated_by' => $note->updated_by, 'created_at' => $note->created_at?->toISOString(), 'updated_at' => $note->updated_at?->toISOString()]]; }
}
