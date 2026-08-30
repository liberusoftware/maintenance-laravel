<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Procurement\Actions\CreateVendorContract;
use Liberu\Modules\Maintenance\Procurement\Actions\DeleteVendorContract;
use Liberu\Modules\Maintenance\Procurement\Actions\TransitionVendorContract;
use Liberu\Modules\Maintenance\Procurement\Actions\UpdateVendorContract;
use Liberu\Modules\Maintenance\Procurement\Models\VendorContract;

class VendorContractController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('viewAny', VendorContract::class), 403);
        $query = VendorContract::query()->where('team_id', $teamId);
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->string('window')->toString() === 'expiring') {
            $query->expiringSoon(max(0, min($request->integer('days', 30), 365)));
        } elseif ($request->string('window')->toString() === 'expired') {
            $query->expired();
        }
        $items = $query->orderBy('end_date')->paginate(min($request->integer('per_page', 25), 100));

        return response()->json(['data' => $items->getCollection()->map(fn (VendorContract $contract): array => $this->resource($contract))->values(), 'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()]]);
    }

    public function store(Request $request, CreateVendorContract $create): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('create', VendorContract::class), 403);
        $data = $request->validate(['vendor_name' => 'required|string|max:255', 'contract_number' => 'required|string|max:255', 'title' => 'required|string|max:255', 'description' => 'nullable|string|max:10000', 'contract_type' => 'nullable|in:service,maintenance,supply,other', 'start_date' => 'required|date', 'end_date' => 'required|date|after_or_equal:start_date', 'contract_value' => 'nullable|numeric|min:0', 'currency' => 'nullable|string|size:3', 'auto_renewal' => 'nullable|boolean', 'renewal_date' => 'nullable|date', 'notes' => 'nullable|string|max:10000', 'metadata' => 'nullable|array']);

        return response()->json(['data' => $this->resource($create->handle($teamId, $data))], 201);
    }

    public function show(Request $request, VendorContract $vendorContract): JsonResponse
    {
        abort_unless($this->teamId($request) === (int) $vendorContract->team_id && $request->user()->can('view', $vendorContract), 404);

        return response()->json(['data' => $this->resource($vendorContract)]);
    }

    public function transition(Request $request, VendorContract $vendorContract, TransitionVendorContract $transition): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($teamId === (int) $vendorContract->team_id && $request->user()->can('update', $vendorContract), 404);
        $data = $request->validate(['status' => 'required|in:active,expired,terminated,renewed']);

        return response()->json(['data' => $this->resource($transition->handle($teamId, $vendorContract, $data['status']))]);
    }

    public function update(Request $request, VendorContract $vendorContract, UpdateVendorContract $update): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($teamId === (int) $vendorContract->team_id && $request->user()->can('update', $vendorContract), 404);
        $data = $request->validate(['vendor_name' => 'sometimes|required|string|max:255', 'contract_number' => 'sometimes|required|string|max:255', 'title' => 'sometimes|required|string|max:255', 'description' => 'sometimes|nullable|string|max:10000', 'contract_type' => 'sometimes|in:service,maintenance,supply,other', 'start_date' => 'sometimes|required|date', 'end_date' => 'sometimes|required|date|after_or_equal:start_date', 'contract_value' => 'sometimes|nullable|numeric|min:0', 'currency' => 'sometimes|string|size:3', 'auto_renewal' => 'sometimes|boolean', 'renewal_date' => 'sometimes|nullable|date', 'notes' => 'sometimes|nullable|string|max:10000', 'metadata' => 'sometimes|nullable|array']);

        return response()->json(['data' => $this->resource($update->handle($teamId, $vendorContract, $data))]);
    }

    public function destroy(Request $request, VendorContract $vendorContract, DeleteVendorContract $delete): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($teamId === (int) $vendorContract->team_id && $request->user()->can('delete', $vendorContract), 404);
        $delete->handle($teamId, $vendorContract);

        return response()->json(null, 204);
    }

    private function teamId(Request $request): ?int
    {
        $id = $request->user()?->currentTeam?->getKey();

        return $id === null ? null : (int) $id;
    }

    private function resource(VendorContract $contract): array
    {
        return ['id' => (string) $contract->getKey(), 'type' => 'maintenance-vendor-contract', 'attributes' => ['vendor_name' => $contract->vendor_name, 'contract_number' => $contract->contract_number, 'title' => $contract->title, 'description' => $contract->description, 'contract_type' => $contract->contract_type, 'start_date' => $contract->start_date?->toDateString(), 'end_date' => $contract->end_date?->toDateString(), 'contract_value' => $contract->contract_value, 'currency' => $contract->currency, 'status' => $contract->status, 'auto_renewal' => $contract->auto_renewal, 'renewal_date' => $contract->renewal_date?->toDateString(), 'days_until_expiration' => $contract->daysUntilExpiration(), 'notes' => $contract->notes, 'metadata' => $contract->metadata]];
    }
}
