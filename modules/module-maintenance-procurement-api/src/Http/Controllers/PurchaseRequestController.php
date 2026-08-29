<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Procurement\Actions\CreatePurchaseRequest;
use Liberu\Modules\Maintenance\Procurement\Actions\ApprovePurchaseRequest;
use Liberu\Modules\Maintenance\Procurement\Models\PurchaseRequest;

class PurchaseRequestController extends Controller
{
    public function index(Request $r): JsonResponse
    {
        $id = $this->teamId($r);
        abort_if($id === null, 403);
        abort_unless($r->user()->can('viewAny', PurchaseRequest::class), 403);
        $items = PurchaseRequest::where('team_id', $id)->latest()->paginate(min($r->integer('per_page', 25), 100));

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

    private function teamId(Request $r): ?int
    {
        $id = $r->user()?->currentTeam?->getKey();

        return $id === null ? null : (int) $id;
    }

    private function resource(PurchaseRequest $p): array
    {
        return ['id' => (string) $p->getKey(), 'type' => 'maintenance-purchase-request', 'attributes' => ['supplier_name' => $p->supplier_name, 'title' => $p->title, 'description' => $p->description, 'amount' => $p->amount, 'currency' => $p->currency, 'status' => $p->status, 'requested_by' => $p->requested_by, 'approved_by' => $p->approved_by]];
    }
}
