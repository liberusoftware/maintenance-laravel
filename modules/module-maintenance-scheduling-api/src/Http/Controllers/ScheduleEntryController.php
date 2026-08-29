<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Scheduling\Actions\CreateScheduleEntry;
use Liberu\Modules\Maintenance\Scheduling\Models\ScheduleEntry;

class ScheduleEntryController extends Controller
{
    public function index(Request $r): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($r->user()->can('viewAny', ScheduleEntry::class), 403);
        $items = ScheduleEntry::where('team_id', $id)->orderBy('starts_at')->paginate(min($r->integer('per_page', 25), 100));

        return response()->json(['data' => $items->getCollection()->map(fn (ScheduleEntry $e) => $this->resource($e))->values(), 'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()]]);
    }

    public function store(Request $r, CreateScheduleEntry $create): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($r->user()->can('create', ScheduleEntry::class), 403);
        $data = $r->validate(['title' => 'required|string|max:255', 'resource_key' => 'nullable|string|max:255', 'starts_at' => 'required|date', 'ends_at' => 'required|date|after:starts_at', 'status' => 'nullable|string|max:64', 'territory' => 'nullable|string|max:255', 'metadata' => 'nullable|array']);

        return response()->json(['data' => $this->resource($create->handle($id, $data))], 201);
    }

    public function show(Request $r, ScheduleEntry $scheduleEntry): JsonResponse
    {
        abort_unless($this->teamId($r) === $scheduleEntry->team_id && $r->user()->can('view', $scheduleEntry), 404);

        return response()->json(['data' => $this->resource($scheduleEntry)]);
    }

    private function teamId(Request $r): ?int
    {
        $id = $r->user()?->currentTeam?->getKey();

        return $id === null ? null : (int) $id;
    }

    private function resource(ScheduleEntry $e): array
    {
        return ['id' => (string) $e->getKey(), 'type' => 'maintenance-schedule-entry', 'attributes' => ['title' => $e->title, 'resource_key' => $e->resource_key, 'starts_at' => $e->starts_at?->toISOString(), 'ends_at' => $e->ends_at?->toISOString(), 'status' => $e->status, 'territory' => $e->territory, 'metadata' => $e->metadata]];
    }
}
