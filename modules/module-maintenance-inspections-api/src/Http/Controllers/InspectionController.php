<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Inspections\Actions\CompleteInspection;
use Liberu\Modules\Maintenance\Inspections\Actions\CreateInspection;
use Liberu\Modules\Maintenance\Inspections\Actions\DeleteInspection;
use Liberu\Modules\Maintenance\Inspections\Actions\UpdateInspection;
use Liberu\Modules\Maintenance\Inspections\Models\Inspection;

class InspectionController extends Controller
{
    public function index(Request $r): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($r->user()->can('viewAny', Inspection::class), 403);
        $items = Inspection::where('team_id', $id)->latest()->paginate(min($r->integer('per_page', 25), 100));

        return response()->json(['data' => $items->getCollection()->map(fn (Inspection $i) => $this->resource($i))->values(), 'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()]]);
    }

    public function store(Request $r, CreateInspection $create): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($r->user()->can('create', Inspection::class), 403);
        $data = $r->validate(['title' => 'required|string|max:255', 'template_key' => 'nullable|string|max:255', 'readings' => 'nullable|array', 'failures' => 'nullable|array', 'follow_up' => 'nullable|array']);
        $data['inspector_id'] = $r->user()->getKey();

        return response()->json(['data' => $this->resource($create->handle($id, $data))], 201);
    }

    public function show(Request $r, Inspection $inspection): JsonResponse
    {
        abort_unless($this->teamId($r) === $inspection->team_id && $r->user()->can('view', $inspection), 404);

        return response()->json(['data' => $this->resource($inspection)]);
    }

    public function complete(Request $r, Inspection $inspection, CompleteInspection $complete): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $inspection->team_id && $r->user()->can('update', $inspection), 404);
        $data = $r->validate(['outcome' => ['required', 'in:pass,fail,conditional']]);

        return response()->json(['data' => $this->resource($complete->handle($id, $inspection, $data['outcome']))]);
    }

    public function update(Request $r, Inspection $inspection, UpdateInspection $update): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $inspection->team_id && $r->user()->can('update', $inspection), 404);
        $data = $r->validate(['title' => 'sometimes|required|string|max:255', 'template_key' => 'sometimes|nullable|string|max:255', 'readings' => 'sometimes|nullable|array', 'failures' => 'sometimes|nullable|array', 'follow_up' => 'sometimes|nullable|array']);

        return response()->json(['data' => $this->resource($update->handle($id, $inspection, $data))]);
    }

    public function destroy(Request $r, Inspection $inspection, DeleteInspection $delete): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $inspection->team_id && $r->user()->can('delete', $inspection), 404);
        $delete->handle($id, $inspection);

        return response()->json(null, 204);
    }

    private function teamId(Request $r): ?int
    {
        $id = $r->user()?->currentTeam?->getKey();

        return $id === null ? null : (int) $id;
    }

    private function resource(Inspection $i): array
    {
        return ['id' => (string) $i->getKey(), 'type' => 'maintenance-inspection', 'attributes' => ['title' => $i->title, 'template_key' => $i->template_key, 'status' => $i->status, 'outcome' => $i->outcome, 'inspected_at' => $i->inspected_at?->toISOString(), 'readings' => $i->readings, 'failures' => $i->failures, 'follow_up' => $i->follow_up]];
    }
}
