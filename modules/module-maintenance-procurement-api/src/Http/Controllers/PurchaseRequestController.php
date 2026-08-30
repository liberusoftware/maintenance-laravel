<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Procurement\Actions\ApprovePurchaseRequest;
use Liberu\Modules\Maintenance\Procurement\Actions\CreatePurchaseRequest;
use Liberu\Modules\Maintenance\Procurement\Actions\DeletePurchaseRequest;
use Liberu\Modules\Maintenance\Procurement\Actions\RejectPurchaseRequest;
use Liberu\Modules\Maintenance\Procurement\Actions\TransitionPurchaseRequest;
use Liberu\Modules\Maintenance\Procurement\Actions\UpdatePurchaseRequest;
use Liberu\Modules\Maintenance\Procurement\Models\PurchaseRequest;

class PurchaseRequestController extends Controller
{
    public function index(Request $r): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($r->user()->can('viewAny', PurchaseRequest::class), 403);
        $query = PurchaseRequest::query()->where('team_id', $id);
        if ($r->filled('status')) {
            $query->where('status', $r->string('status')->toString());
        }
        if ($r->filled('requested_by')) {
            $query->where('requested_by', $r->integer('requested_by'));
        }
        $items = $query->latest()->paginate(min($r->integer('per_page', 25), 100));

        return response()->json(['data' => $items->getCollection()->map(fn (PurchaseRequest $p) => $this->resource($p))->values(), 'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()]]);
    }

    public function store(Request $r, CreatePurchaseRequest $create): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($r->user()->can('create', PurchaseRequest::class), 403);
        $data = $r->validate(['supplier_name' => 'nullable|string|max:255', 'title' => 'required|string|max:255', 'description' => 'nullable|string|max:10000', 'amount' => 'required|numeric|min:0', 'currency' => 'nullable|string|size:3', 'metadata' => 'nullable|array']);
        $data['requested_by'] = $r->user()->getKey();

        return response()->json(['data' => $this->resource($create->handle($id, $data))], 201);
    }

    public function show(Request $r, PurchaseRequest $purchaseRequest): JsonResponse
    {
        abort_unless($this->teamId($r) === $purchaseRequest->team_id && $r->user()->can('view', $purchaseRequest), 404);

        return response()->json(['data' => $this->resource($purchaseRequest)]);
    }

    public function approve(Request $r, PurchaseRequest $purchaseRequest, ApprovePurchaseRequest $approve): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $purchaseRequest->team_id && $r->user()->can('update', $purchaseRequest), 404);

        return response()->json(['data' => $this->resource($approve->handle($id, $purchaseRequest, (int) $r->user()->getKey()))]);
    }

    public function reject(Request $r, PurchaseRequest $purchaseRequest, RejectPurchaseRequest $reject): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $purchaseRequest->team_id && $r->user()->can('update', $purchaseRequest), 404);
        $data = $r->validate(['reason' => 'sometimes|nullable|string|max:2000']);

        return response()->json(['data' => $this->resource($reject->handle($id, $purchaseRequest, (int) $r->user()->getKey(), $data['reason'] ?? null))]);
    }

    public function transition(Request $r, PurchaseRequest $purchaseRequest, TransitionPurchaseRequest $transition): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $purchaseRequest->team_id && $r->user()->can('update', $purchaseRequest), 404);
        $data = $r->validate(['status' => ['required', 'string', 'in:ordered,received,cancelled']]);

        return response()->json(['data' => $this->resource($transition->handle($id, $purchaseRequest, $data['status'], (int) $r->user()->getKey()))]);
    }

    public function update(Request $r, PurchaseRequest $purchaseRequest, UpdatePurchaseRequest $update): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $purchaseRequest->team_id && $r->user()->can('update', $purchaseRequest), 404);
        $data = $r->validate(['supplier_name' => 'sometimes|nullable|string|max:255', 'title' => 'sometimes|required|string|max:255', 'description' => 'sometimes|nullable|string|max:10000', 'amount' => 'sometimes|required|numeric|min:0', 'currency' => 'sometimes|string|size:3', 'metadata' => 'sometimes|nullable|array']);

        return response()->json(['data' => $this->resource($update->handle($id, $purchaseRequest, $data))]);
    }

    public function destroy(Request $r, PurchaseRequest $purchaseRequest, DeletePurchaseRequest $delete): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($id === (int) $purchaseRequest->team_id && $r->user()->can('delete', $purchaseRequest), 404);
        $delete->handle($id, $purchaseRequest);

        return response()->json(null, 204);
    }

    private function teamId(Request $r): ?int
    {
        $id = $r->user()?->currentTeam?->getKey();

        return $id === null ? null : (int) $id;
    }

    private function resource(PurchaseRequest $p): array
    {
        return ['id' => (string) $p->getKey(), 'type' => 'maintenance-purchase-request', 'attributes' => ['supplier_name' => $p->supplier_name, 'title' => $p->title, 'description' => $p->description, 'amount' => $p->amount, 'currency' => $p->currency, 'status' => $p->status, 'requested_by' => $p->requested_by, 'approved_by' => $p->approved_by, 'metadata' => $p->metadata]];
    }
}
