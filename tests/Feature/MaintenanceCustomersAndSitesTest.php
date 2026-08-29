<?php

use Illuminate\Validation\ValidationException;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\CreateCustomer;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\CreateSite;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\UpdateSite;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Customer;
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

it('updates a site without allowing cross-team customer reassignment', function () {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $customer = app(CreateCustomer::class)->handle($team->id, ['name' => 'Acme', 'code' => 'ACME']);
    $otherCustomer = app(CreateCustomer::class)->handle($otherTeam->id, ['name' => 'Other', 'code' => 'OTHER']);
    $site = app(CreateSite::class)->handle($team->id, ['customer_id' => $customer->id, 'name' => 'HQ', 'code' => 'HQ']);

    expect(fn () => app(UpdateSite::class)->handle($team->id, $site, ['customer_id' => $otherCustomer->id]))
        ->toThrow(ValidationException::class);
});
