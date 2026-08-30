<?php

use Illuminate\Validation\ValidationException;
use App\Models\User;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\CreateContact;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\CreateCustomer;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\CreateLocation;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\CreateSite;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\CreateServiceWindow;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\UpdateContact;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\UpdateLocation;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\UpdateSite;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\UpdateServiceWindow;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Contact;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Customer;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Location;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\ServiceWindow;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Site;

it('creates customers and sites only inside the current team boundary', function () {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $customer = app(CreateCustomer::class)->handle($team->id, ['name' => 'Acme', 'code' => 'acme']);
    $site = app(CreateSite::class)->handle($team->id, ['customer_id' => $customer->id, 'name' => 'Acme HQ', 'code' => 'hq']);

    expect($customer)->toBeInstanceOf(Customer::class)
        ->and($site)->toBeInstanceOf(Site::class)
        ->and(Customer::where('team_id', $otherTeam->id)->count())->toBe(0);

    expect(fn () => app(CreateSite::class)->handle($otherTeam->id, ['customer_id' => $customer->id, 'name' => 'Leak', 'code' => 'LEAK']))
        ->toThrow(ValidationException::class);
});

it('rejects duplicate customer codes within a team', function () {
    $team = Team::factory()->create();
    $action = app(CreateCustomer::class);
    $action->handle($team->id, ['name' => 'Acme', 'code' => 'ACME']);

    expect(fn () => $action->handle($team->id, ['name' => 'Other', 'code' => 'acme']))
        ->toThrow(ValidationException::class);
});

it('retains legacy customer profile fields and vendor classification', function () {
    $team = Team::factory()->create();
    $customer = app(CreateCustomer::class)->handle($team->id, [
        'name' => 'Acme Services', 'code' => 'acme', 'type' => 'supplier', 'address' => '1 Main Street',
        'city' => 'Denver', 'state' => 'CO', 'zip' => '80202', 'website' => 'https://acme.test',
        'industry' => 'Facilities', 'contact_person' => 'Jane Doe', 'payment_terms' => 'Net 30',
    ]);

    expect($customer->code)->toBe('ACME')
        ->and($customer->isSupplier())->toBeTrue()
        ->and($customer->isVendor())->toBeTrue()
        ->and($customer->isCustomer())->toBeFalse()
        ->and(Customer::query()->where('team_id', $team->id)->suppliers()->whereKey($customer)->exists())->toBeTrue()
        ->and(Customer::query()->where('team_id', $team->id)->active()->whereKey($customer)->exists())->toBeTrue()
        ->and($customer->city)->toBe('Denver');
});

it('updates a site without allowing cross-team customer reassignment', function () {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $customer = app(CreateCustomer::class)->handle($team->id, ['name' => 'Acme', 'code' => 'ACME']);
    $otherCustomer = app(CreateCustomer::class)->handle($otherTeam->id, ['name' => 'Other', 'code' => 'OTHER']);
    $site = app(CreateSite::class)->handle($team->id, ['customer_id' => $customer->id, 'name' => 'HQ', 'code' => 'HQ']);

    expect(fn () => app(UpdateSite::class)->handle($team->id, $site, ['customer_id' => $otherCustomer->id]))
        ->toThrow(ValidationException::class);
});

it('provides active and inactive site scopes', function () {
    $team = Team::factory()->create();
    $customer = app(CreateCustomer::class)->handle($team->id, ['name' => 'Acme', 'code' => 'ACME']);
    $active = app(CreateSite::class)->handle($team->id, ['customer_id' => $customer->id, 'name' => 'HQ', 'code' => 'HQ']);
    $inactive = app(CreateSite::class)->handle($team->id, ['customer_id' => $customer->id, 'name' => 'Depot', 'code' => 'DEPOT', 'is_active' => false]);

    expect(Site::query()->where('team_id', $team->id)->active()->whereKey($active)->exists())->toBeTrue()
        ->and(Site::query()->where('team_id', $team->id)->inactive()->whereKey($inactive)->exists())->toBeTrue();
});

it('supports tenant-scoped customer contacts and primary contact ownership', function () {
    $team = Team::factory()->create();
    $customer = app(CreateCustomer::class)->handle($team->id, ['name' => 'Acme', 'code' => 'ACME']);
    $create = app(CreateContact::class);
    $first = $create->handle($team->id, ['customer_id' => $customer->id, 'name' => 'Jane', 'email' => 'jane@example.test', 'is_primary' => true]);
    $second = $create->handle($team->id, ['customer_id' => $customer->id, 'name' => 'John', 'is_primary' => true]);

    expect($first)->toBeInstanceOf(Contact::class)
        ->and($second->is_primary)->toBeTrue()
        ->and($first->refresh()->is_primary)->toBeFalse()
        ->and($customer->refresh()->contacts()->count())->toBe(2);

    $updated = app(UpdateContact::class)->handle($team->id, $second, ['role' => 'Facilities manager']);
    expect($updated->role)->toBe('Facilities manager');
});

it('supports site locations and prevents cross-team reassignment', function () {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $customer = app(CreateCustomer::class)->handle($team->id, ['name' => 'Acme', 'code' => 'ACME']);
    $otherCustomer = app(CreateCustomer::class)->handle($otherTeam->id, ['name' => 'Other', 'code' => 'OTHER']);
    $site = app(CreateSite::class)->handle($team->id, ['customer_id' => $customer->id, 'name' => 'HQ', 'code' => 'HQ']);
    $otherSite = app(CreateSite::class)->handle($otherTeam->id, ['customer_id' => $otherCustomer->id, 'name' => 'Other HQ', 'code' => 'OTHER-HQ']);
    $location = app(CreateLocation::class)->handle($team->id, ['site_id' => $site->id, 'name' => 'Boiler room', 'hazards' => 'Hot surfaces', 'latitude' => 39.7392, 'longitude' => -104.9903]);

    expect($location)->toBeInstanceOf(Location::class)
        ->and($site->refresh()->locations()->whereKey($location)->exists())->toBeTrue()
        ->and(fn () => app(UpdateLocation::class)->handle($team->id, $location, ['site_id' => $otherSite->id]))
        ->toThrow(ValidationException::class);
});

it('validates non-overlapping site service windows and supports updates', function () {
    $team = Team::factory()->create();
    $customer = app(CreateCustomer::class)->handle($team->id, ['name' => 'Acme', 'code' => 'ACME']);
    $site = app(CreateSite::class)->handle($team->id, ['customer_id' => $customer->id, 'name' => 'HQ', 'code' => 'HQ']);
    $create = app(CreateServiceWindow::class);
    $window = $create->handle($team->id, ['site_id' => $site->id, 'weekday' => 1, 'starts_at' => '08:00', 'ends_at' => '16:00', 'timezone' => 'UTC']);

    expect($window)->toBeInstanceOf(ServiceWindow::class)
        ->and(fn () => $create->handle($team->id, ['site_id' => $site->id, 'weekday' => 1, 'starts_at' => '15:00', 'ends_at' => '17:00']))
        ->toThrow(ValidationException::class);

    $updated = app(UpdateServiceWindow::class)->handle($team->id, $window, ['starts_at' => '09:00', 'ends_at' => '17:00']);
    expect($updated->starts_at)->toBe('09:00');
});

it('exposes contacts locations and service windows through the tenant API', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();
    $token = $user->createToken('customers-sites-api-test')->plainTextToken;
    $customer = app(CreateCustomer::class)->handle($team->id, ['name' => 'Acme', 'code' => 'ACME']);
    $site = app(CreateSite::class)->handle($team->id, ['customer_id' => $customer->id, 'name' => 'HQ', 'code' => 'HQ']);

    $contact = $this->withToken($token)->postJson('/api/v1/maintenance/customers-and-sites/contacts', ['customer_id' => $customer->id, 'name' => 'Jane'])->assertCreated()->json('data.id');
    $location = $this->withToken($token)->postJson('/api/v1/maintenance/customers-and-sites/locations', ['site_id' => $site->id, 'name' => 'Plant room'])->assertCreated()->json('data.id');
    $window = $this->withToken($token)->postJson('/api/v1/maintenance/customers-and-sites/service-windows', ['site_id' => $site->id, 'weekday' => 1, 'starts_at' => '08:00', 'ends_at' => '16:00'])->assertCreated()->json('data.id');

    $this->withToken($token)->patchJson("/api/v1/maintenance/customers-and-sites/contacts/{$contact}", ['role' => 'Site manager'])->assertOk()->assertJsonPath('data.attributes.role', 'Site manager');
    $this->withToken($token)->patchJson("/api/v1/maintenance/customers-and-sites/locations/{$location}", ['hazards' => 'Restricted area'])->assertOk();
    $this->withToken($token)->patchJson("/api/v1/maintenance/customers-and-sites/service-windows/{$window}", ['ends_at' => '17:00'])->assertOk();

    $this->withToken($token)->getJson('/api/v1/maintenance/customers-and-sites/contacts')->assertOk()->assertJsonCount(1, 'data');
    $this->withToken($token)->getJson('/api/v1/maintenance/customers-and-sites/locations')->assertOk()->assertJsonCount(1, 'data');
    $this->withToken($token)->getJson('/api/v1/maintenance/customers-and-sites/service-windows')->assertOk()->assertJsonCount(1, 'data');
});
