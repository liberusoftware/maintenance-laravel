<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\WorkOrders\Actions\AddWorkOrderComment;
use Liberu\Modules\Maintenance\WorkOrders\Actions\AddWorkOrderDependency;
use Liberu\Modules\Maintenance\WorkOrders\Actions\AddWorkOrderEvidence;
use Liberu\Modules\Maintenance\WorkOrders\Actions\CreateWorkOrder;
use Liberu\Modules\Maintenance\WorkOrders\Actions\DeleteWorkOrder;
use Liberu\Modules\Maintenance\WorkOrders\Actions\RemoveWorkOrderDependency;
use Liberu\Modules\Maintenance\WorkOrders\Actions\RemoveWorkOrderEvidence;
use Liberu\Modules\Maintenance\WorkOrders\Actions\TransitionWorkOrder;
use Liberu\Modules\Maintenance\WorkOrders\Actions\UpdateWorkOrder;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrder;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrderComment;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrderDependency;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrderEvidence;

class WorkOrderController extends Controller
{
    public function index(Request $r): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($r->user()->can('viewAny', WorkOrder::class), 403);
        $query = WorkOrder::where('team_id', $id);
        $query = match ($r->string('window')->toString()) {
            'overdue' => $query->overdue(),
            'due_within' => $query->dueWithin(max(1, min($r->integer('days', 7), 365))),
            default => $query,
        };
        if ($r->filled('assigned_to')) {
            $query->assignedToUser($r->integer('assigned_to'));
        }
        if ($r->filled('status')) {
            $query->where('status', $r->string('status')->toString());
        }
        $items = $query->latest()->paginate(min($r->integer('per_page', 25), 100));

        return response()->json(['data' => $items->getCollection()->map(fn (WorkOrder $o) => $this->resource($o))->values(), 'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()]]);
    }

    public function store(Request $r, CreateWorkOrder $create): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($r->user()->can('create', WorkOrder::class), 403);
        $data = $r->validate($this->rules());

        return response()->json(['data' => $this->resource($create->handle($id, $data))], 201);
    }

    public function show(Request $r, WorkOrder $workOrder): JsonResponse
    {
        abort_unless($this->teamId($r) === $workOrder->team_id && $r->user()->can('view', $workOrder), 404);

        return response()->json(['data' => $this->resource($workOrder)]);
    }

    public function update(Request $r, WorkOrder $workOrder, UpdateWorkOrder $update): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $workOrder->team_id && $r->user()->can('update', $workOrder), 404);
        $data = $r->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'priority' => ['sometimes', 'nullable', 'string', 'max:64'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'equipment_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'customer_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'vendor_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'assigned_to' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'guest_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'guest_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'guest_phone' => ['sometimes', 'nullable', 'string', 'max:64'],
            'submitted_at' => ['sometimes', 'nullable', 'date'],
            'reviewed_by' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'reviewed_at' => ['sometimes', 'nullable', 'date'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'started_at' => ['sometimes', 'nullable', 'date'],
            'estimated_minutes' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'actual_minutes' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'maintenance_plan_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'checklist_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ]);

        return response()->json(['data' => $this->resource($update->handle($id, $workOrder, $data))]);
    }

    public function destroy(Request $r, WorkOrder $workOrder, DeleteWorkOrder $delete): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $workOrder->team_id && $r->user()->can('delete', $workOrder), 404);
        $delete->handle($id, $workOrder);

        return response()->noContent();
    }

    public function transition(Request $r, WorkOrder $workOrder, TransitionWorkOrder $transition): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $workOrder->team_id && $r->user()->can('update', $workOrder), 404);
        $data = $r->validate(['status' => ['required', 'string', 'max:64']]);

        return response()->json(['data' => $this->resource($transition->handle($id, $workOrder, $data['status']))]);
    }

    public function comments(Request $r, WorkOrder $workOrder): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $workOrder->team_id && $r->user()->can('view', $workOrder), 404);

        return response()->json(['data' => $workOrder->comments()->latest()->get()->map(fn (WorkOrderComment $comment): array => $this->commentResource($comment))->values()]);
    }

    public function comment(Request $r, WorkOrder $workOrder, AddWorkOrderComment $add): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $workOrder->team_id && $r->user()->can('update', $workOrder), 404);
        $data = $r->validate(['comment' => ['required', 'string', 'max:10000'], 'is_internal' => ['sometimes', 'boolean']]);

        return response()->json(['data' => $this->commentResource($add->handle($id, $workOrder, (int) $r->user()->getAuthIdentifier(), $data['comment'], (bool) ($data['is_internal'] ?? false)))], 201);
    }

    public function dependencies(Request $r, WorkOrder $workOrder): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $workOrder->team_id && $r->user()->can('view', $workOrder), 404);
        $items = $workOrder->dependencies()->with('dependsOn')->latest()->get();

        return response()->json(['data' => $items->map(fn (WorkOrderDependency $dependency): array => $this->dependencyResource($dependency))->values()]);
    }

    public function addDependency(Request $r, WorkOrder $workOrder, AddWorkOrderDependency $add): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $workOrder->team_id && $r->user()->can('update', $workOrder), 404);
        $data = $r->validate(['depends_on_work_order_id' => ['required', 'integer', 'min:1']]);
        $dependsOn = WorkOrder::query()->findOrFail($data['depends_on_work_order_id']);

        return response()->json(['data' => $this->dependencyResource($add->handle($id, $workOrder, $dependsOn))], 201);
    }

    public function removeDependency(Request $r, WorkOrder $workOrder, WorkOrderDependency $dependency, RemoveWorkOrderDependency $remove): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $workOrder->team_id && (int) $dependency->work_order_id === (int) $workOrder->getKey() && $r->user()->can('update', $workOrder), 404);
        $remove->handle($id, $dependency);

        return response()->noContent();
    }

    public function evidence(Request $r, WorkOrder $workOrder): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $workOrder->team_id && $r->user()->can('view', $workOrder), 404);

        return response()->json(['data' => $workOrder->evidence()->latest()->get()->map(fn (WorkOrderEvidence $evidence): array => $this->evidenceResource($evidence))->values()]);
    }

    public function addEvidence(Request $r, WorkOrder $workOrder, AddWorkOrderEvidence $add): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $workOrder->team_id && $r->user()->can('update', $workOrder), 404);
        $data = $r->validate(['kind' => ['required', 'string', 'max:64'], 'label' => ['required', 'string', 'max:255'], 'reference' => ['required', 'string', 'max:10000'], 'metadata' => ['sometimes', 'nullable', 'array']]);
        $data['added_by'] = $r->user()->getAuthIdentifier();

        return response()->json(['data' => $this->evidenceResource($add->handle($id, $workOrder, $data))], 201);
    }

    public function removeEvidence(Request $r, WorkOrder $workOrder, WorkOrderEvidence $evidence, RemoveWorkOrderEvidence $remove): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $workOrder->team_id && (int) $evidence->work_order_id === (int) $workOrder->getKey() && $r->user()->can('update', $workOrder), 404);
        $remove->handle($id, $evidence);

        return response()->noContent();
    }

    private function teamId(Request $r): ?int
    {
        $id = $r->user()?->currentTeam?->getKey();

        return $id === null ? null : (int) $id;
    }

    private function resource(WorkOrder $o): array
    {
        return ['id' => (string) $o->getKey(), 'type' => 'maintenance-work-order', 'attributes' => ['number' => $o->number, 'title' => $o->title, 'description' => $o->description, 'location' => $o->location, 'equipment_id' => $o->equipment_id, 'customer_id' => $o->customer_id, 'vendor_id' => $o->vendor_id, 'assigned_to' => $o->assigned_to, 'guest_name' => $o->guest_name, 'guest_email' => $o->guest_email, 'guest_phone' => $o->guest_phone, 'submitted_at' => $o->submitted_at?->toISOString(), 'reviewed_by' => $o->reviewed_by, 'reviewed_at' => $o->reviewed_at?->toISOString(), 'due_date' => $o->due_date?->toISOString(), 'started_at' => $o->started_at?->toISOString(), 'estimated_minutes' => $o->estimated_minutes, 'actual_minutes' => $o->actual_minutes, 'maintenance_plan_id' => $o->maintenance_plan_id, 'checklist_id' => $o->checklist_id, 'priority' => $o->priority, 'status' => $o->status, 'completed_at' => $o->completed_at?->toISOString(), 'notes' => $o->notes, 'metadata' => $o->metadata, 'created_at' => $o->created_at?->toISOString(), 'updated_at' => $o->updated_at?->toISOString()]];
    }

    private function dependencyResource(WorkOrderDependency $dependency): array
    {
        return ['id' => (string) $dependency->getKey(), 'type' => 'maintenance-work-order-dependency', 'attributes' => ['work_order_id' => (string) $dependency->work_order_id, 'depends_on_work_order_id' => (string) $dependency->depends_on_work_order_id, 'depends_on' => $dependency->dependsOn === null ? null : ['number' => $dependency->dependsOn->number, 'title' => $dependency->dependsOn->title], 'created_at' => $dependency->created_at?->toISOString()]];
    }

    private function evidenceResource(WorkOrderEvidence $evidence): array
    {
        return ['id' => (string) $evidence->getKey(), 'type' => 'maintenance-work-order-evidence', 'attributes' => ['work_order_id' => (string) $evidence->work_order_id, 'added_by' => $evidence->added_by, 'kind' => $evidence->kind, 'label' => $evidence->label, 'reference' => $evidence->reference, 'metadata' => $evidence->metadata, 'created_at' => $evidence->created_at?->toISOString()]];
    }

    /** @return array<string, mixed> */
    private function commentResource(WorkOrderComment $comment): array
    {
        return ['id' => (string) $comment->getKey(), 'type' => 'maintenance-work-order-comment', 'attributes' => ['work_order_id' => (string) $comment->work_order_id, 'user_id' => $comment->user_id, 'comment' => $comment->comment, 'is_internal' => $comment->is_internal, 'created_at' => $comment->created_at?->toISOString()]];
    }

    /** @return array<string, array<int, string>> */
    private function rules(): array
    {
        return ['title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:10000'], 'priority' => ['nullable', 'string', 'max:64'], 'metadata' => ['nullable', 'array'], 'location' => ['nullable', 'string', 'max:255'], 'equipment_id' => ['nullable', 'integer', 'min:1'], 'customer_id' => ['nullable', 'integer', 'min:1'], 'vendor_id' => ['nullable', 'integer', 'min:1'], 'assigned_to' => ['nullable', 'integer', 'min:1'], 'guest_name' => ['nullable', 'string', 'max:255'], 'guest_email' => ['nullable', 'email', 'max:255'], 'guest_phone' => ['nullable', 'string', 'max:64'], 'submitted_at' => ['nullable', 'date'], 'reviewed_by' => ['nullable', 'integer', 'min:1'], 'reviewed_at' => ['nullable', 'date'], 'notes' => ['nullable', 'string', 'max:10000'], 'due_date' => ['nullable', 'date'], 'started_at' => ['nullable', 'date'], 'estimated_minutes' => ['nullable', 'integer', 'min:0'], 'actual_minutes' => ['nullable', 'integer', 'min:0'], 'maintenance_plan_id' => ['nullable', 'integer', 'min:1'], 'checklist_id' => ['nullable', 'integer', 'min:1']];
    }
}
