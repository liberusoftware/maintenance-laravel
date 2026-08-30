<?php

use App\Models\User;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\Assets\Actions\CreateAsset;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Actions\CreateMaintenancePlan;
use Liberu\Modules\Maintenance\Report\Services\MaintenanceReportService;
use Liberu\Modules\Maintenance\WorkOrders\Actions\CreateWorkOrder;
use Liberu\Modules\Maintenance\WorkOrders\Actions\TransitionWorkOrder;

it('recreates legacy maintenance report metrics inside the reporting domain', function () {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);
    $asset = app(CreateAsset::class)->handle($team->id, ['name' => 'Cooling pump', 'code' => 'PUMP-1', 'serial_number' => 'SN-1']);
    $order = app(CreateWorkOrder::class)->handle($team->id, [
        'title' => 'Repair cooling pump',
        'equipment_id' => $asset->id,
        'assigned_to' => $user->id,
        'priority' => 'high',
        'metadata' => ['parts_cost' => 125, 'labor_cost' => 75],
    ]);
    $order->forceFill(['submitted_at' => now()->subHours(5), 'started_at' => now()->subHours(3), 'actual_minutes' => 90])->save();
    app(TransitionWorkOrder::class)->handle($team->id, $order, 'triaged');
    app(TransitionWorkOrder::class)->handle($team->id, $order->refresh(), 'in_progress');
    $order->forceFill(['started_at' => now()->subHours(3), 'completed_at' => now()])->save();
    app(TransitionWorkOrder::class)->handle($team->id, $order->refresh(), 'completed');

    $report = app(MaintenanceReportService::class)->generateComprehensiveReport($team->id, now()->subDay(), now()->addMinute());

    expect($report['mttr'])->toBe(3.0)
        ->and($report['cost_analysis']['parts_cost'])->toBe(125.0)
        ->and($report['cost_analysis']['labor_cost'])->toBe(75.0)
        ->and($report['equipment_performance'][0]['equipment_name'])->toBe('Cooling pump')
        ->and($report['technician_performance'][0]['technician_id'])->toBe($user->id)
        ->and($report['trends']['this_week_total'])->toBe(1);
});

it('includes overdue preventative plans in comprehensive report insights', function () {
    $team = Team::factory()->create();
    app(CreateMaintenancePlan::class)->handle($team->id, [
        'name' => 'Boiler inspection',
        'code' => 'BOILER-1',
        'frequency_unit' => 'months',
        'frequency_value' => 1,
        'next_due_at' => now()->subDay(),
    ]);

    $report = app(MaintenanceReportService::class)->generateComprehensiveReport($team->id);

    expect(collect($report['actionable_insights'])->pluck('category'))->toContain('Preventative Maintenance');
});

it('exposes the comprehensive report through the tenant API', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();
    $token = $user->createToken('comprehensive-report-test')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/maintenance/reporting/comprehensive')
        ->assertOk()
        ->assertJsonStructure(['data' => ['period', 'mttr', 'cost_analysis', 'equipment_performance', 'technician_performance', 'trends', 'actionable_insights']]);
});
