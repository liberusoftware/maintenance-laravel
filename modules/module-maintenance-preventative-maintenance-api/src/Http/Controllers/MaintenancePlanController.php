<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\PreventativeMaintenance\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Actions\CreateMaintenancePlan;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Models\MaintenancePlan;

class MaintenancePlanController extends Controller
{
    public function index(Request $r): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($r->user()->can('viewAny', MaintenancePlan::class), 403);
        $items = MaintenancePlan::where('team_id', $id)->orderBy('name')->paginate(min($r->integer('per_page', 25), 100));

        return response()->json(['data' => $items->getCollection()->map(fn (MaintenancePlan $p) => $this->resource($p))->values(), 'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()]]);
    }

    public function store(Request $r, CreateMaintenancePlan $create): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($r->user()->can('create', MaintenancePlan::class), 403);
        $data = $r->validate(['name' => 'required|string|max:255', 'code' => 'required|string|max:64', 'frequency_unit' => 'nullable|in:days,weeks,months,meters', 'frequency_value' => 'required|integer|min:1', 'next_due_at' => 'nullable|date', 'rules' => 'nullable|array']);

        return response()->json(['data' => $this->resource($create->handle($id, $data))], 201);
    }

    public function show(Request $r, MaintenancePlan $maintenancePlan): JsonResponse
    {
        abort_unless($this->teamId($r) === $maintenancePlan->team_id && $r->user()->can('view', $maintenancePlan), 404);

        return response()->json(['data' => $this->resource($maintenancePlan)]);
    }

    private function teamId(Request $r): ?int
    {
        $id = $r->user()?->currentTeam?->getKey();

        return $id === null ? null : (int) $id;
    }

    private function resource(MaintenancePlan $p): array
    {
        return ['id' => (string) $p->getKey(), 'type' => 'maintenance-preventative-plan', 'attributes' => ['name' => $p->name, 'code' => $p->code, 'frequency_unit' => $p->frequency_unit, 'frequency_value' => $p->frequency_value, 'next_due_at' => $p->next_due_at?->toISOString(), 'is_active' => $p->is_active, 'rules' => $p->rules]];
    }
}
