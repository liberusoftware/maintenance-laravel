<?php

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Actions\CompleteMaintenancePlan;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Actions\CreateMaintenancePlan;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Actions\GenerateWorkOrderFromPlan;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Actions\UpdateMaintenancePlan;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Models\MaintenancePlan;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrder;

it('creates a tenant-scoped preventative maintenance plan', function () {
    $team = Team::factory()->create();
    $plan = app(CreateMaintenancePlan::class)->handle($team->id, ['name' => 'Pump service', 'code' => 'pump', 'frequency_value' => 30]);

    expect($plan)->toBeInstanceOf(MaintenancePlan::class)
        ->and($plan->team_id)->toBe($team->id)
        ->and($plan->code)->toBe('PUMP')
        ->and($plan->frequency_unit)->toBe('days');
});

it('recalculates the next due date when a plan is completed', function () {
    $team = Team::factory()->create();
    $plan = app(CreateMaintenancePlan::class)->handle($team->id, [
        'name' => 'Pump service', 'code' => 'pump', 'frequency_unit' => 'weeks', 'frequency_value' => 2,
        'next_due_at' => Carbon::parse('2026-08-01'),
    ]);

    $completed = app(CompleteMaintenancePlan::class)->handle($team->id, $plan, Carbon::parse('2026-08-10 09:00:00'));

    expect($completed->last_completed_at->toDateTimeString())->toBe('2026-08-10 09:00:00')
        ->and($completed->next_due_at->toDateTimeString())->toBe('2026-08-24 09:00:00');
});

it('finds overdue and upcoming active plans through scopes', function () {
    $team = Team::factory()->create();
    $create = app(CreateMaintenancePlan::class);
    $create->handle($team->id, ['name' => 'Late', 'code' => 'late', 'frequency_value' => 30, 'next_due_at' => now()->subDay()]);
    $create->handle($team->id, ['name' => 'Soon', 'code' => 'soon', 'frequency_value' => 30, 'next_due_at' => now()->addDays(3)]);
    $create->handle($team->id, ['name' => 'Inactive', 'code' => 'inactive', 'frequency_value' => 30, 'next_due_at' => now()->subDay(), 'is_active' => false]);

    expect(MaintenancePlan::query()->where('team_id', $team->id)->overdue()->count())->toBe(1)
        ->and(MaintenancePlan::query()->where('team_id', $team->id)->upcoming(7)->count())->toBe(1);
});

it('supports hourly and yearly recurrence when completing a plan', function () {
    $team = Team::factory()->create();
    $create = app(CreateMaintenancePlan::class);
    $hourly = $create->handle($team->id, ['name' => 'Hourly check', 'code' => 'hourly', 'frequency_unit' => 'hours', 'frequency_value' => 6]);
    $yearly = $create->handle($team->id, ['name' => 'Yearly check', 'code' => 'yearly', 'frequency_unit' => 'years', 'frequency_value' => 1]);

    $hourly = app(CompleteMaintenancePlan::class)->handle($team->id, $hourly, Carbon::parse('2026-08-10 09:00:00'));
    $yearly = app(CompleteMaintenancePlan::class)->handle($team->id, $yearly, Carbon::parse('2026-08-10 09:00:00'));

    expect($hourly->next_due_at->toDateTimeString())->toBe('2026-08-10 15:00:00')
        ->and($yearly->next_due_at->toDateTimeString())->toBe('2027-08-10 09:00:00');
});

it('rejects duplicate preventative plan codes within a team', function () {
    $team = Team::factory()->create();
    $action = app(CreateMaintenancePlan::class);
    $action->handle($team->id, ['name' => 'Pump service', 'code' => 'PUMP', 'frequency_value' => 30]);

    expect(fn () => $action->handle($team->id, ['name' => 'Other', 'code' => 'pump', 'frequency_value' => 60]))
        ->toThrow(ValidationException::class);
});

it('persists preventative frequency unit changes through the update action', function () {
    $team = Team::factory()->create();
    $plan = app(CreateMaintenancePlan::class)->handle($team->id, [
        'name' => 'Pump service', 'code' => 'PUMP-1', 'frequency_unit' => 'days', 'frequency_value' => 7,
    ]);

    $updated = app(UpdateMaintenancePlan::class)->handle($team->id, $plan, ['frequency_unit' => 'weeks', 'frequency_value' => 2]);

    expect($updated->frequency_unit)->toBe('weeks')
        ->and($updated->frequency_value)->toBe(2);
});

it('retains legacy preventative schedule details in the modular plan', function () {
    $team = Team::factory()->create();
    $plan = app(CreateMaintenancePlan::class)->handle($team->id, [
        'name' => 'Pump service', 'code' => 'pump-details', 'description' => 'Monthly pump inspection',
        'equipment_id' => 41, 'assigned_to' => 52, 'checklist_id' => 63, 'instructions' => 'Lock out before inspection.',
        'estimated_duration' => 90, 'frequency_value' => 30,
    ]);

    expect($plan->description)->toBe('Monthly pump inspection')
        ->and($plan->equipment_id)->toBe(41)
        ->and($plan->assigned_to)->toBe(52)
        ->and($plan->checklist_id)->toBe(63)
        ->and($plan->instructions)->toBe('Lock out before inspection.')
        ->and($plan->estimated_duration)->toBe(90);
});

it('generates one linked work order from an active preventative plan', function () {
    $team = Team::factory()->create();
    $plan = app(CreateMaintenancePlan::class)->handle($team->id, [
        'name' => 'Pump service', 'code' => 'pump-generated', 'priority' => 'high',
        'description' => 'Inspect the pump', 'frequency_value' => 30,
    ]);

    $generate = app(GenerateWorkOrderFromPlan::class);
    $workOrder = $generate->handle($team->id, $plan);
    $sameWorkOrder = $generate->handle($team->id, $plan->refresh());

    expect($workOrder)->toBeInstanceOf(WorkOrder::class)
        ->and($workOrder->maintenance_plan_id)->toBe($plan->id)
        ->and($workOrder->priority)->toBe('high')
        ->and($sameWorkOrder->id)->toBe($workOrder->id)
        ->and($plan->workOrders()->count())->toBe(1);
});

it('generates preventative work orders through the tenant API', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();
    $token = $user->createToken('preventative-generation-test')->plainTextToken;
    $plan = $this->withToken($token)->postJson('/api/v1/maintenance/preventative-maintenance', [
        'name' => 'Filter service', 'code' => 'filter-api', 'frequency_value' => 14,
    ])->assertCreated()->json('data.id');

    $this->withToken($token)->postJson("/api/v1/maintenance/preventative-maintenance/{$plan}/generate-work-order")
        ->assertCreated()->assertJsonPath('data.attributes.maintenance_plan_id', (int) $plan);
});
