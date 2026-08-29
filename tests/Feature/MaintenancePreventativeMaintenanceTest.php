<?php

use Illuminate\Validation\ValidationException;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Actions\CreateMaintenancePlan;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Models\MaintenancePlan;

it('creates a tenant-scoped preventative maintenance plan', function () {
    $team = Team::factory()->create();
    $plan = app(CreateMaintenancePlan::class)->handle($team->id, ['name' => 'Pump service', 'code' => 'pump', 'frequency_value' => 30]);

    expect($plan)->toBeInstanceOf(MaintenancePlan::class)
        ->and($plan->team_id)->toBe($team->id)
        ->and($plan->code)->toBe('PUMP')
        ->and($plan->frequency_unit)->toBe('days');
});

it('rejects duplicate preventative plan codes within a team', function () {
    $team = Team::factory()->create();
    $action = app(CreateMaintenancePlan::class);
    $action->handle($team->id, ['name' => 'Pump service', 'code' => 'PUMP', 'frequency_value' => 30]);

    expect(fn () => $action->handle($team->id, ['name' => 'Other', 'code' => 'pump', 'frequency_value' => 60]))
        ->toThrow(ValidationException::class);
});
