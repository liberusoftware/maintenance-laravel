<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\CreateCustomer;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\CreateSite;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\DeleteCustomer;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\DeleteSite;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\UpdateCustomer;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\UpdateSite;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Customer;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Site;

class CustomersAndSitesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('viewAny', Customer::class), 403);
        $query = Customer::where('team_id', $teamId);
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->trim()->toString());
        }
        $items = $query->orderBy('name')->paginate(min($request->integer('per_page', 25), 100));

        return response()->json(['data' => $items->getCollection()->map(fn (Customer $c) => $this->resource($c))->values(), 'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()]]);
    }

    public function store(Request $request, CreateCustomer $create): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('create', Customer::class), 403);
        $data = $request->validate(['name' => 'required|string|max:255', 'code' => 'required|string|max:64', 'email' => 'nullable|email|max:255', 'phone' => 'nullable|string|max:64', 'address' => 'nullable|string|max:10000', 'city' => 'nullable|string|max:255', 'state' => 'nullable|string|max:255', 'zip' => 'nullable|string|max:32', 'website' => 'nullable|url|max:255', 'industry' => 'nullable|string|max:255', 'description' => 'nullable|string|max:10000', 'type' => 'nullable|in:customer,vendor,supplier,both', 'contact_person' => 'nullable|string|max:255', 'payment_terms' => 'nullable|string|max:255', 'notes' => 'nullable|string|max:10000']);

        return response()->json(['data' => $this->resource($create->handle($teamId, $data))], 201);
    }

    public function show(Request $request, Customer $customer): JsonResponse
    {
        abort_unless($this->teamId($request) === $customer->team_id && $request->user()->can('view', $customer), 404);

        return response()->json(['data' => $this->resource($customer)]);
    }

    public function update(Request $request, Customer $customer, UpdateCustomer $update): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($teamId === (int) $customer->team_id && $request->user()->can('update', $customer), 404);
        $data = $request->validate(['name' => 'sometimes|required|string|max:255', 'code' => 'sometimes|required|string|max:64', 'email' => 'sometimes|nullable|email|max:255', 'phone' => 'sometimes|nullable|string|max:64', 'address' => 'sometimes|nullable|string|max:10000', 'city' => 'sometimes|nullable|string|max:255', 'state' => 'sometimes|nullable|string|max:255', 'zip' => 'sometimes|nullable|string|max:32', 'website' => 'sometimes|nullable|url|max:255', 'industry' => 'sometimes|nullable|string|max:255', 'description' => 'sometimes|nullable|string|max:10000', 'type' => 'sometimes|in:customer,vendor,supplier,both', 'contact_person' => 'sometimes|nullable|string|max:255', 'payment_terms' => 'sometimes|nullable|string|max:255', 'notes' => 'sometimes|nullable|string|max:10000', 'is_active' => 'sometimes|boolean']);

        return response()->json(['data' => $this->resource($update->handle($teamId, $customer, $data))]);
    }

    public function destroy(Request $request, Customer $customer, DeleteCustomer $delete): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($teamId === (int) $customer->team_id && $request->user()->can('delete', $customer), 404);
        $delete->handle($teamId, $customer);

        return response()->json(null, 204);
    }

    public function sites(Request $request): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('viewAny', Site::class), 403);
        $query = Site::query()->where('team_id', $teamId)->with('customer');
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->integer('customer_id'));
        }
        $items = $query->orderBy('name')->paginate(min($request->integer('per_page', 25), 100));

        return response()->json(['data' => $items->getCollection()->map(fn (Site $site) => $this->siteResource($site))->values(), 'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()]]);
    }

    public function storeSite(Request $request, CreateSite $create): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('create', Site::class), 403);
        $data = $request->validate(['customer_id' => 'required|integer', 'name' => 'required|string|max:255', 'code' => 'required|string|max:64', 'address' => 'nullable|string|max:10000', 'access_details' => 'nullable|string|max:10000', 'hazards' => 'nullable|string|max:10000']);

        return response()->json(['data' => $this->siteResource($create->handle($teamId, $data))], 201);
    }

    public function updateSite(Request $request, Site $site, UpdateSite $update): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($teamId === (int) $site->team_id && $request->user()->can('update', $site), 404);
        $data = $request->validate(['customer_id' => 'sometimes|integer', 'name' => 'sometimes|required|string|max:255', 'code' => 'sometimes|required|string|max:64', 'address' => 'sometimes|nullable|string|max:10000', 'access_details' => 'sometimes|nullable|string|max:10000', 'hazards' => 'sometimes|nullable|string|max:10000', 'is_active' => 'sometimes|boolean']);

        return response()->json(['data' => $this->siteResource($update->handle($teamId, $site, $data))]);
    }

    public function destroySite(Request $request, Site $site, DeleteSite $delete): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($teamId === (int) $site->team_id && $request->user()->can('delete', $site), 404);
        $delete->handle($teamId, $site);

        return response()->json(null, 204);
    }

    private function teamId(Request $request): ?int
    {
        $id = $request->user()?->currentTeam?->getKey();

        return $id === null ? null : (int) $id;
    }

    private function resource(Customer $c): array
    {
        return ['id' => (string) $c->getKey(), 'type' => 'maintenance-customer', 'attributes' => ['name' => $c->name, 'code' => $c->code, 'email' => $c->email, 'phone' => $c->phone, 'address' => $c->address, 'city' => $c->city, 'state' => $c->state, 'zip' => $c->zip, 'website' => $c->website, 'industry' => $c->industry, 'description' => $c->description, 'type' => $c->type, 'contact_person' => $c->contact_person, 'payment_terms' => $c->payment_terms, 'notes' => $c->notes, 'is_active' => $c->is_active, 'created_at' => $c->created_at?->toISOString(), 'updated_at' => $c->updated_at?->toISOString()]];
    }

    private function siteResource(Site $site): array
    {
        return ['id' => (string) $site->getKey(), 'type' => 'maintenance-site', 'attributes' => ['customer_id' => $site->customer_id, 'name' => $site->name, 'code' => $site->code, 'address' => $site->address, 'access_details' => $site->access_details, 'hazards' => $site->hazards, 'is_active' => $site->is_active]];
    }
}
