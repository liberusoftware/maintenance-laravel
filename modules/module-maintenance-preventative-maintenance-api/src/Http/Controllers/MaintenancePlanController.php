<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\PreventativeMaintenance\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Actions\CompleteMaintenancePlan;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Actions\CreateMaintenancePlan;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Actions\DeleteMaintenancePlan;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Actions\UpdateMaintenancePlan;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Models\MaintenancePlan;

class MaintenancePlanController extends Controller
{
    public function index(Request $r): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($r->user()->can('viewAny', MaintenancePlan::class), 403);
        $query = MaintenancePlan::where('team_id', $id);
        $query = match ($r->string('window')->toString()) {
            'overdue' => $query->overdue(),
            'due_soon' => $query->dueSoon(max(1, min($r->integer('days', 7), 365))),
            'upcoming' => $query->upcoming(max(1, min($r->integer('days', 30), 365))),
            default => $query->orderBy('name'),
        };
        $items = $query->paginate(min($r->integer('per_page', 25), 100));

        return response()->json(['data' => $items->getCollection()->map(fn (MaintenancePlan $p) => $this->resource($p))->values(), 'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()]]);
    }

    public function complete(Request $r, MaintenancePlan $maintenancePlan, CompleteMaintenancePlan $complete): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $maintenancePlan->team_id && $r->user()->can('update', $maintenancePlan), 404);
        $data = $r->validate(['completed_at' => 'sometimes|date']);
        $completedAt = isset($data['completed_at']) ? now()->parse($data['completed_at']) : null;

        return response()->json(['data' => $this->resource($complete->handle($id, $maintenancePlan, $completedAt))]);
    }

    public function store(Request $r, CreateMaintenancePlan $create): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($r->user()->can('create', MaintenancePlan::class), 403);
        $data = $r->validate(['name' => 'required|string|max:255', 'code' => 'required|string|max:64', 'frequency_unit' => 'nullable|in:hours,days,weeks,months,years,meters', 'frequency_value' => 'required|integer|min:1', 'next_due_at' => 'nullable|date', 'rules' => 'nullable|array']);

        return response()->json(['data' => $this->resource($create->handle($id, $data))], 201);
    }

    public function show(Request $r, MaintenancePlan $maintenancePlan): JsonResponse
    {
        abort_unless($this->teamId($r) === $maintenancePlan->team_id && $r->user()->can('view', $maintenancePlan), 404);

        return response()->json(['data' => $this->resource($maintenancePlan)]);
    }

    public function update(Request $r, MaintenancePlan $maintenancePlan, UpdateMaintenancePlan $update): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $maintenancePlan->team_id && $r->user()->can('update', $maintenancePlan), 404);
        $data = $r->validate(['name' => 'sometimes|required|string|max:255', 'code' => 'sometimes|required|string|max:64', 'frequency_unit' => 'sometimes|in:hours,days,weeks,months,years,meters', 'frequency_value' => 'sometimes|required|integer|min:1', 'next_due_at' => 'sometimes|nullable|date', 'is_active' => 'sometimes|boolean', 'rules' => 'sometimes|nullable|array']);

        return response()->json(['data' => $this->resource($update->handle($id, $maintenancePlan, $data))]);
    }

    public function destroy(Request $r, MaintenancePlan $maintenancePlan, DeleteMaintenancePlan $delete): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $maintenancePlan->team_id && $r->user()->can('delete', $maintenancePlan), 404);
        $delete->handle($id, $maintenancePlan);

        return response()->json(null, 204);
    }

    private function teamId(Request $r): ?int
    {
        $id = $r->user()?->currentTeam?->getKey();

        return $id === null ? null : (int) $id;
    }

    private function resource(MaintenancePlan $p): array
    {
        return ['id' => (string) $p->getKey(), 'type' => 'maintenance-preventative-plan', 'attributes' => ['name' => $p->name, 'code' => $p->code, 'frequency_unit' => $p->frequency_unit, 'frequency_value' => $p->frequency_value, 'next_due_at' => $p->next_due_at?->toISOString(), 'last_completed_at' => $p->last_completed_at?->toISOString(), 'is_active' => $p->is_active, 'rules' => $p->rules]];
    }
}
