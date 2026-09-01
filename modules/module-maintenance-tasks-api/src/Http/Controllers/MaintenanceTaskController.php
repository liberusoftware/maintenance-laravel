<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Tasks\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Tasks\Actions\CompleteMaintenanceTask;
use Liberu\Modules\Maintenance\Tasks\Actions\CreateMaintenanceTask;
use Liberu\Modules\Maintenance\Tasks\Models\MaintenanceTask;

final class MaintenanceTaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_unless($request->user()->can('viewAny', MaintenanceTask::class), 403);
        $query = MaintenanceTask::query()->where('team_id', $teamId);
        foreach (['status', 'assigned_to'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }
        if ($request->boolean('overdue')) {
            $query->overdue();
        }
        $tasks = $query->latest()->paginate(min($request->integer('per_page', 25), 100));

        return response()->json(['data' => $tasks->getCollection()->map(fn (MaintenanceTask $task): array => $this->resource($task))->values(), 'meta' => ['total' => $tasks->total()]]);
    }

    public function store(Request $request, CreateMaintenanceTask $create): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_unless($request->user()->can('create', MaintenanceTask::class), 403);
        $data = $request->validate(['description' => ['required', 'string', 'max:10000'], 'status' => ['sometimes', 'in:pending,in_progress,cancelled'], 'priority' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:255'], 'due_date' => ['sometimes', 'nullable', 'date'], 'assigned_to' => ['sometimes', 'nullable', 'integer', 'min:1'], 'taskable_type' => ['sometimes', 'nullable', 'string', 'max:255'], 'taskable_id' => ['sometimes', 'nullable', 'integer', 'min:1']]);
        $data['created_by'] = (int) $request->user()->getKey();

        return response()->json(['data' => $this->resource($create->handle($teamId, $data))], 201);
    }

    public function show(Request $request, string $task): JsonResponse
    {
        $task = $this->task($request, $task);
        abort_unless($request->user()->can('view', $task), 404);

        return response()->json(['data' => $this->resource($task)]);
    }

    public function update(Request $request, string $task): JsonResponse
    {
        $task = $this->task($request, $task);
        abort_unless($request->user()->can('update', $task), 404);
        $task->update(array_merge($request->validate(['description' => ['sometimes', 'required', 'string', 'max:10000'], 'status' => ['sometimes', 'in:pending,in_progress,cancelled'], 'priority' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:255'], 'due_date' => ['sometimes', 'nullable', 'date'], 'assigned_to' => ['sometimes', 'nullable', 'integer', 'min:1']]), ['updated_by' => (int) $request->user()->getKey()]));

        return response()->json(['data' => $this->resource($task->refresh())]);
    }

    public function complete(Request $request, string $task, CompleteMaintenanceTask $complete): JsonResponse
    {
        $task = $this->task($request, $task);
        abort_unless($request->user()->can('update', $task), 404);

        return response()->json(['data' => $this->resource($complete->handle($this->teamId($request), $task))]);
    }

    public function destroy(Request $request, string $task): JsonResponse
    {
        $task = $this->task($request, $task);
        abort_unless($request->user()->can('delete', $task), 404);
        $task->delete();

        return response()->json(null, 204);
    }

    private function teamId(Request $request): int
    {
        $id = $request->user()?->currentTeam?->getKey();
        abort_if($id === null, 403);

        return (int) $id;
    }

    private function task(Request $request, string $key): MaintenanceTask
    {
        return MaintenanceTask::query()->where('team_id', $this->teamId($request))->findOrFail($key);
    }

    private function resource(MaintenanceTask $task): array
    {
        return ['id' => (string) $task->getKey(), 'type' => 'maintenance-task', 'attributes' => ['description' => $task->description, 'status' => $task->status, 'priority' => $task->priority, 'due_date' => $task->due_date?->toDateString(), 'assigned_to' => $task->assigned_to, 'taskable_type' => $task->taskable_type, 'taskable_id' => $task->taskable_id, 'completed_at' => $task->completed_at?->toISOString(), 'created_by' => $task->created_by]];
    }
}
