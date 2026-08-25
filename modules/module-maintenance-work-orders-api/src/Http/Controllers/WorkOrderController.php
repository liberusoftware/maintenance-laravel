<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\WorkOrders\Actions\CreateWorkOrder;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrder;

class WorkOrderController extends Controller
{
    public function index(Request $r): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($r->user()->can('viewAny', WorkOrder::class), 403);
        $items = WorkOrder::where('team_id', $id)->latest()->paginate(min($r->integer('per_page', 25), 100));

        return response()->json(['data' => $items->getCollection()->map(fn (WorkOrder $o) => $this->resource($o))->values(), 'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()]]);
    }

    public function store(Request $r, CreateWorkOrder $create): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($r->user()->can('create', WorkOrder::class), 403);
        $data = $r->validate(['title' => 'required|string|max:255', 'description' => 'nullable|string|max:10000', 'priority' => 'nullable|string|max:64', 'metadata' => 'nullable|array']);

        return response()->json(['data' => $this->resource($create->handle($id, $data))], 201);
    }

    public function show(Request $r, WorkOrder $o): JsonResponse
    {
        abort_unless($this->teamId($r) === $o->team_id && $r->user()->can('view', $o), 404);

        return response()->json(['data' => $this->resource($o)]);
    }

    private function teamId(Request $r): ?int
    {
        $id = $r->user()?->currentTeam?->getKey();

        return $id === null ? null : (int) $id;
    }

    private function resource(WorkOrder $o): array
    {
        return ['id' => (string) $o->getKey(), 'type' => 'maintenance-work-order', 'attributes' => ['number' => $o->number, 'title' => $o->title, 'description' => $o->description, 'priority' => $o->priority, 'status' => $o->status, 'completed_at' => $o->completed_at?->toISOString(), 'metadata' => $o->metadata, 'created_at' => $o->created_at?->toISOString(), 'updated_at' => $o->updated_at?->toISOString()]];
    }
}
