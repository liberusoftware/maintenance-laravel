<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\LaborAndTime\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\LaborAndTime\Actions\ApproveTimeEntry;
use Liberu\Modules\Maintenance\LaborAndTime\Actions\CreateTimeEntry;
use Liberu\Modules\Maintenance\LaborAndTime\Actions\DeleteTimeEntry;
use Liberu\Modules\Maintenance\LaborAndTime\Actions\UpdateTimeEntry;
use Liberu\Modules\Maintenance\LaborAndTime\Models\TimeEntry;

class TimeEntryController extends Controller
{
    public function index(Request $r): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($r->user()->can('viewAny', TimeEntry::class), 403);
        $items = TimeEntry::where('team_id', $id)->latest()->paginate(min($r->integer('per_page', 25), 100));

        return response()->json(['data' => $items->getCollection()->map(fn (TimeEntry $e) => $this->resource($e))->values(), 'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()]]);
    }

    public function store(Request $r, CreateTimeEntry $create): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($r->user()->can('create', TimeEntry::class), 403);
        $data = $r->validate(['description' => 'nullable|string|max:255', 'minutes' => 'required|integer|min:1', 'rate' => 'nullable|numeric|min:0', 'expense_amount' => 'nullable|numeric|min:0', 'currency' => 'nullable|string|size:3', 'started_at' => 'nullable|date', 'ended_at' => 'nullable|date']);
        $data['user_id'] = $r->user()->getKey();

        return response()->json(['data' => $this->resource($create->handle($id, $data))], 201);
    }

    public function show(Request $r, TimeEntry $timeEntry): JsonResponse
    {
        abort_unless($this->teamId($r) === $timeEntry->team_id && $r->user()->can('view', $timeEntry), 404);

        return response()->json(['data' => $this->resource($timeEntry)]);
    }

    public function approve(Request $r, TimeEntry $timeEntry, ApproveTimeEntry $approve): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $timeEntry->team_id && $r->user()->can('update', $timeEntry), 404);

        return response()->json(['data' => $this->resource($approve->handle($id, $timeEntry, (int) $r->user()->getKey()))]);
    }

    public function update(Request $r, TimeEntry $timeEntry, UpdateTimeEntry $update): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $timeEntry->team_id && $r->user()->can('update', $timeEntry), 404);
        $data = $r->validate(['description' => 'sometimes|nullable|string|max:255', 'minutes' => 'sometimes|required|integer|min:1', 'rate' => 'sometimes|nullable|numeric|min:0', 'expense_amount' => 'sometimes|nullable|numeric|min:0', 'currency' => 'sometimes|string|size:3', 'started_at' => 'sometimes|nullable|date', 'ended_at' => 'sometimes|nullable|date']);

        return response()->json(['data' => $this->resource($update->handle($id, $timeEntry, $data))]);
    }

    public function destroy(Request $r, TimeEntry $timeEntry, DeleteTimeEntry $delete): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $timeEntry->team_id && $r->user()->can('delete', $timeEntry), 404);
        $delete->handle($id, $timeEntry);

        return response()->json(null, 204);
    }

    private function teamId(Request $r): ?int
    {
        $id = $r->user()?->currentTeam?->getKey();

        return $id === null ? null : (int) $id;
    }

    private function resource(TimeEntry $e): array
    {
        return ['id' => (string) $e->getKey(), 'type' => 'maintenance-time-entry', 'attributes' => ['description' => $e->description, 'minutes' => $e->minutes, 'rate' => $e->rate, 'status' => $e->status, 'expense_amount' => $e->expense_amount, 'currency' => $e->currency, 'started_at' => $e->started_at?->toISOString(), 'ended_at' => $e->ended_at?->toISOString()]];
    }
}
