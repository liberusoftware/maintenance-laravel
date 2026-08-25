<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\CreateCustomer;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Customer;

class CustomersAndSitesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('viewAny', Customer::class), 403);
        $items = Customer::where('team_id', $teamId)->orderBy('name')->paginate(min($request->integer('per_page', 25), 100));

        return response()->json(['data' => $items->getCollection()->map(fn (Customer $c) => $this->resource($c))->values(), 'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()]]);
    }

    public function store(Request $request, CreateCustomer $create): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('create', Customer::class), 403);
        $data = $request->validate(['name' => 'required|string|max:255', 'code' => 'required|string|max:64', 'email' => 'nullable|email|max:255', 'phone' => 'nullable|string|max:64', 'notes' => 'nullable|string|max:10000']);

        return response()->json(['data' => $this->resource($create->handle($teamId, $data))], 201);
    }

    public function show(Request $request, Customer $customer): JsonResponse
    {
        abort_unless($this->teamId($request) === $customer->team_id && $request->user()->can('view', $customer), 404);

        return response()->json(['data' => $this->resource($customer)]);
    }

    private function teamId(Request $request): ?int
    {
        $id = $request->user()?->currentTeam?->getKey();

        return $id === null ? null : (int) $id;
    }

    private function resource(Customer $c): array
    {
        return ['id' => (string) $c->getKey(), 'type' => 'maintenance-customer', 'attributes' => ['name' => $c->name, 'code' => $c->code, 'email' => $c->email, 'phone' => $c->phone, 'notes' => $c->notes, 'is_active' => $c->is_active, 'created_at' => $c->created_at?->toISOString(), 'updated_at' => $c->updated_at?->toISOString()]];
    }
}
