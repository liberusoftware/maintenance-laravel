<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\CreateContact;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\CreateCustomer;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\CreateLocation;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\CreateServiceWindow;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\CreateSite;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\DeleteContact;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\DeleteCustomer;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\DeleteLocation;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\DeleteServiceWindow;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\DeleteSite;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\UpdateContact;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\UpdateCustomer;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\UpdateLocation;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\UpdateServiceWindow;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\UpdateSite;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Contact;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Customer;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Location;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\ServiceWindow;
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

    public function contacts(Request $request): JsonResponse
    {
        $teamId = $this->authorizedTeam($request, Contact::class, 'viewAny');
        $query = Contact::query()->where('team_id', $teamId)->with('customer');
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->integer('customer_id'));
        }
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        $items = $query->orderBy('name')->paginate(min($request->integer('per_page', 25), 100));

        return response()->json(['data' => $items->getCollection()->map(fn (Contact $contact): array => $this->contactResource($contact))->values(), 'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()]]);
    }

    public function storeContact(Request $request, CreateContact $create): JsonResponse
    {
        $teamId = $this->authorizedTeam($request, Contact::class, 'create');
        $data = $request->validate(['customer_id' => 'required|integer|min:1', 'name' => 'required|string|max:255', 'email' => 'nullable|email|max:255', 'phone' => 'nullable|string|max:64', 'role' => 'nullable|string|max:255', 'is_primary' => 'nullable|boolean', 'is_active' => 'nullable|boolean', 'notes' => 'nullable|string|max:10000']);

        return response()->json(['data' => $this->contactResource($create->handle($teamId, $data))], 201);
    }

    public function updateContact(Request $request, string $contact, UpdateContact $update): JsonResponse
    {
        $contact = $this->recordForTeam($request, Contact::class, $contact);
        $teamId = $this->authorizedRecord($request, $contact, 'update');
        $data = $request->validate(['customer_id' => 'sometimes|integer|min:1', 'name' => 'sometimes|required|string|max:255', 'email' => 'sometimes|nullable|email|max:255', 'phone' => 'sometimes|nullable|string|max:64', 'role' => 'sometimes|nullable|string|max:255', 'is_primary' => 'sometimes|boolean', 'is_active' => 'sometimes|boolean', 'notes' => 'sometimes|nullable|string|max:10000']);

        return response()->json(['data' => $this->contactResource($update->handle($teamId, $contact, $data))]);
    }

    public function destroyContact(Request $request, string $contact, DeleteContact $delete): JsonResponse
    {
        $contact = $this->recordForTeam($request, Contact::class, $contact);
        $teamId = $this->authorizedRecord($request, $contact, 'delete');
        $delete->handle($teamId, $contact);

        return response()->json(null, 204);
    }

    public function locations(Request $request): JsonResponse
    {
        $teamId = $this->authorizedTeam($request, Location::class, 'viewAny');
        $query = Location::query()->where('team_id', $teamId)->with('site');
        if ($request->filled('site_id')) {
            $query->where('site_id', $request->integer('site_id'));
        }
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        $items = $query->orderBy('name')->paginate(min($request->integer('per_page', 25), 100));

        return response()->json(['data' => $items->getCollection()->map(fn (Location $location): array => $this->locationResource($location))->values(), 'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()]]);
    }

    public function storeLocation(Request $request, CreateLocation $create): JsonResponse
    {
        $teamId = $this->authorizedTeam($request, Location::class, 'create');
        $data = $request->validate(['site_id' => 'required|integer|min:1', 'name' => 'required|string|max:255', 'address' => 'nullable|string|max:10000', 'access_details' => 'nullable|string|max:10000', 'hazards' => 'nullable|string|max:10000', 'latitude' => 'nullable|numeric|between:-90,90', 'longitude' => 'nullable|numeric|between:-180,180', 'is_active' => 'nullable|boolean']);

        return response()->json(['data' => $this->locationResource($create->handle($teamId, $data))], 201);
    }

    public function updateLocation(Request $request, string $location, UpdateLocation $update): JsonResponse
    {
        $location = $this->recordForTeam($request, Location::class, $location);
        $teamId = $this->authorizedRecord($request, $location, 'update');
        $data = $request->validate(['site_id' => 'sometimes|integer|min:1', 'name' => 'sometimes|required|string|max:255', 'address' => 'sometimes|nullable|string|max:10000', 'access_details' => 'sometimes|nullable|string|max:10000', 'hazards' => 'sometimes|nullable|string|max:10000', 'latitude' => 'sometimes|nullable|numeric|between:-90,90', 'longitude' => 'sometimes|nullable|numeric|between:-180,180', 'is_active' => 'sometimes|boolean']);

        return response()->json(['data' => $this->locationResource($update->handle($teamId, $location, $data))]);
    }

    public function destroyLocation(Request $request, string $location, DeleteLocation $delete): JsonResponse
    {
        $location = $this->recordForTeam($request, Location::class, $location);
        $teamId = $this->authorizedRecord($request, $location, 'delete');
        $delete->handle($teamId, $location);

        return response()->json(null, 204);
    }

    public function serviceWindows(Request $request): JsonResponse
    {
        $teamId = $this->authorizedTeam($request, ServiceWindow::class, 'viewAny');
        $query = ServiceWindow::query()->where('team_id', $teamId);
        if ($request->filled('site_id')) {
            $query->where('site_id', $request->integer('site_id'));
        }

        return response()->json(['data' => $query->orderBy('weekday')->orderBy('starts_at')->get()->map(fn (ServiceWindow $window): array => $this->serviceWindowResource($window))->values()]);
    }

    public function storeServiceWindow(Request $request, CreateServiceWindow $create): JsonResponse
    {
        $teamId = $this->authorizedTeam($request, ServiceWindow::class, 'create');
        $data = $request->validate(['site_id' => 'required|integer|min:1', 'weekday' => 'required|integer|between:0,6', 'starts_at' => 'required|date_format:H:i', 'ends_at' => 'required|date_format:H:i|after:starts_at', 'timezone' => 'nullable|timezone', 'is_available' => 'nullable|boolean']);

        return response()->json(['data' => $this->serviceWindowResource($create->handle($teamId, $data))], 201);
    }

    public function updateServiceWindow(Request $request, string $serviceWindow, UpdateServiceWindow $update): JsonResponse
    {
        $serviceWindow = $this->recordForTeam($request, ServiceWindow::class, $serviceWindow);
        $teamId = $this->authorizedRecord($request, $serviceWindow, 'update');
        $data = $request->validate(['weekday' => 'sometimes|integer|between:0,6', 'starts_at' => 'sometimes|date_format:H:i', 'ends_at' => 'sometimes|date_format:H:i', 'timezone' => 'sometimes|timezone', 'is_available' => 'sometimes|boolean']);

        return response()->json(['data' => $this->serviceWindowResource($update->handle($teamId, $serviceWindow, $data))]);
    }

    public function destroyServiceWindow(Request $request, string $serviceWindow, DeleteServiceWindow $delete): JsonResponse
    {
        $serviceWindow = $this->recordForTeam($request, ServiceWindow::class, $serviceWindow);
        $teamId = $this->authorizedRecord($request, $serviceWindow, 'delete');
        $delete->handle($teamId, $serviceWindow);

        return response()->json(null, 204);
    }

    private function teamId(Request $request): ?int
    {
        $id = $request->user()?->currentTeam?->getKey();

        return $id === null ? null : (int) $id;
    }

    private function authorizedTeam(Request $request, string $model, string $ability): int
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can($ability, $model), 403);

        return $teamId;
    }

    private function authorizedRecord(Request $request, object $record, string $ability): int
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless((int) $record->team_id === $teamId && $request->user()->can($ability, $record), 404);

        return $teamId;
    }

    private function recordForTeam(Request $request, string $model, string $key): object
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);

        return $model::query()->where('team_id', $teamId)->findOrFail($key);
    }

    private function resource(Customer $c): array
    {
        return ['id' => (string) $c->getKey(), 'type' => 'maintenance-customer', 'attributes' => ['name' => $c->name, 'code' => $c->code, 'email' => $c->email, 'phone' => $c->phone, 'address' => $c->address, 'city' => $c->city, 'state' => $c->state, 'zip' => $c->zip, 'website' => $c->website, 'industry' => $c->industry, 'description' => $c->description, 'type' => $c->type, 'contact_person' => $c->contact_person, 'payment_terms' => $c->payment_terms, 'notes' => $c->notes, 'is_active' => $c->is_active, 'created_at' => $c->created_at?->toISOString(), 'updated_at' => $c->updated_at?->toISOString()]];
    }

    private function siteResource(Site $site): array
    {
        return ['id' => (string) $site->getKey(), 'type' => 'maintenance-site', 'attributes' => ['customer_id' => $site->customer_id, 'name' => $site->name, 'code' => $site->code, 'address' => $site->address, 'access_details' => $site->access_details, 'hazards' => $site->hazards, 'is_active' => $site->is_active]];
    }

    private function contactResource(Contact $contact): array
    {
        return ['id' => (string) $contact->getKey(), 'type' => 'maintenance-contact', 'attributes' => ['customer_id' => $contact->customer_id, 'name' => $contact->name, 'email' => $contact->email, 'phone' => $contact->phone, 'role' => $contact->role, 'is_primary' => $contact->is_primary, 'is_active' => $contact->is_active, 'notes' => $contact->notes]];
    }

    private function locationResource(Location $location): array
    {
        return ['id' => (string) $location->getKey(), 'type' => 'maintenance-location', 'attributes' => ['site_id' => $location->site_id, 'name' => $location->name, 'address' => $location->address, 'access_details' => $location->access_details, 'hazards' => $location->hazards, 'latitude' => $location->latitude, 'longitude' => $location->longitude, 'is_active' => $location->is_active]];
    }

    private function serviceWindowResource(ServiceWindow $window): array
    {
        return ['id' => (string) $window->getKey(), 'type' => 'maintenance-service-window', 'attributes' => ['site_id' => $window->site_id, 'weekday' => $window->weekday, 'starts_at' => $window->starts_at, 'ends_at' => $window->ends_at, 'timezone' => $window->timezone, 'is_available' => $window->is_available]];
    }
}
