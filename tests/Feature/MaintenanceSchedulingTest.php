<?php

use Illuminate\Validation\ValidationException;
use App\Models\User;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\Scheduling\Actions\CreateAvailabilityWindow;
use Liberu\Modules\Maintenance\Scheduling\Actions\CreateEngineerSkill;
use Liberu\Modules\Maintenance\Scheduling\Actions\CreateScheduleEntry;
use Liberu\Modules\Maintenance\Scheduling\Actions\CreateShift;
use Liberu\Modules\Maintenance\Scheduling\Actions\CreateTravelSegment;
use Liberu\Modules\Maintenance\Scheduling\Actions\DispatchScheduleEntry;
use Liberu\Modules\Maintenance\Scheduling\Actions\TransitionScheduleEntry;
use Liberu\Modules\Maintenance\Scheduling\Actions\UpdateScheduleEntry;
use Liberu\Modules\Maintenance\Scheduling\Models\AvailabilityWindow;
use Liberu\Modules\Maintenance\Scheduling\Models\ScheduleEntry;

it('supports tenant-scoped engineer availability through the scheduling API', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();
    $token = $user->createToken('scheduling-api-test')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/maintenance/scheduling/availability', [
        'user_id' => $user->id,
        'weekday' => 1,
        'starts_at' => '08:00',
        'ends_at' => '16:00',
        'timezone' => 'UTC',
    ])->assertCreated()->assertJsonPath('data.attributes.weekday', 1);

    $this->withToken($token)->getJson('/api/v1/maintenance/scheduling/availability?user_id='.$user->id)
        ->assertOk()->assertJsonCount(1, 'data');
});

it('rejects overlapping availability windows for one engineer', function () {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $action = app(CreateAvailabilityWindow::class);
    $action->handle($team->id, ['user_id' => $user->id, 'weekday' => 1, 'starts_at' => '08:00', 'ends_at' => '12:00']);

    expect(fn () => $action->handle($team->id, ['user_id' => $user->id, 'weekday' => 1, 'starts_at' => '11:00', 'ends_at' => '13:00']))
        ->toThrow(ValidationException::class);
});

it('creates tenant-scoped schedule entries', function () {
    $team = Team::factory()->create();
    $entry = app(CreateScheduleEntry::class)->handle($team->id, [
        'title' => 'Pump inspection',
        'resource_key' => 'tech-1',
        'starts_at' => '2026-08-26 09:00:00',
        'ends_at' => '2026-08-26 10:00:00',
    ]);

    expect($entry)->toBeInstanceOf(ScheduleEntry::class)
        ->and($entry->team_id)->toBe($team->id)
        ->and($entry->status)->toBe('scheduled');
});

it('requires assigned engineers to be available when windows are configured', function () {
    $team = Team::factory()->create();
    $engineer = User::factory()->create();
    AvailabilityWindow::create([
        'team_id' => $team->id,
        'user_id' => $engineer->id,
        'weekday' => 1,
        'starts_at' => '08:00',
        'ends_at' => '16:00',
        'timezone' => 'America/New_York',
    ]);
    $action = app(CreateScheduleEntry::class);

    $entry = $action->handle($team->id, [
        'title' => 'Available visit',
        'assigned_to' => $engineer->id,
        'starts_at' => '2026-08-31 13:00:00',
        'ends_at' => '2026-08-31 15:00:00',
        'timezone' => 'UTC',
    ]);

    expect($entry->assigned_to)->toBe($engineer->id)
        ->and(fn () => $action->handle($team->id, [
            'title' => 'Unavailable visit',
            'assigned_to' => $engineer->id,
            'starts_at' => '2026-08-31 21:00:00',
            'ends_at' => '2026-08-31 22:00:00',
            'timezone' => 'UTC',
        ]))->toThrow(ValidationException::class);
});

it('does not allow schedule updates to bypass engineer availability', function () {
    $team = Team::factory()->create();
    $engineer = User::factory()->create();
    app(CreateAvailabilityWindow::class)->handle($team->id, [
        'user_id' => $engineer->id,
        'weekday' => 1,
        'starts_at' => '08:00',
        'ends_at' => '16:00',
        'timezone' => 'UTC',
    ]);
    $entry = app(CreateScheduleEntry::class)->handle($team->id, [
        'title' => 'Available visit',
        'assigned_to' => $engineer->id,
        'starts_at' => '2026-08-31 13:00:00',
        'ends_at' => '2026-08-31 15:00:00',
    ]);

    expect(fn () => app(UpdateScheduleEntry::class)->handle($team->id, $entry, [
        'starts_at' => '2026-08-31 21:00:00',
        'ends_at' => '2026-08-31 22:00:00',
    ]))->toThrow(ValidationException::class);
});

it('retains legacy maintenance schedule details', function () {
    $team = Team::factory()->create();
    $entry = app(CreateScheduleEntry::class)->handle($team->id, [
        'title' => 'Pump inspection', 'description' => 'Monthly inspection', 'equipment_id' => 41,
        'assigned_to' => 52, 'checklist_id' => 63, 'instructions' => 'Lock out before work.',
        'estimated_duration' => 90, 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(),
    ]);

    expect($entry->description)->toBe('Monthly inspection')
        ->and($entry->equipment_id)->toBe(41)
        ->and($entry->assigned_to)->toBe(52)
        ->and($entry->checklist_id)->toBe(63)
        ->and($entry->instructions)->toBe('Lock out before work.')
        ->and($entry->estimated_duration)->toBe(90);
});

it('rejects overlapping entries for the same resource', function () {
    $team = Team::factory()->create();
    $action = app(CreateScheduleEntry::class);
    $attributes = ['resource_key' => 'tech-1', 'starts_at' => '2026-08-26 09:00:00', 'ends_at' => '2026-08-26 10:00:00'];
    $action->handle($team->id, $attributes + ['title' => 'First']);

    expect(fn () => $action->handle($team->id, $attributes + ['title' => 'Conflict']))
        ->toThrow(ValidationException::class);
});

it('provides reusable upcoming and overdue schedule scopes', function () {
    $team = Team::factory()->create();
    $action = app(CreateScheduleEntry::class);
    $action->handle($team->id, ['title' => 'Upcoming', 'resource_key' => 'upcoming', 'starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHour()]);
    $action->handle($team->id, ['title' => 'Overdue', 'resource_key' => 'overdue', 'starts_at' => now()->subDays(2), 'ends_at' => now()->subDay(), 'status' => 'scheduled']);
    $action->handle($team->id, ['title' => 'Cancelled', 'resource_key' => 'cancelled', 'starts_at' => now()->subDays(2), 'ends_at' => now()->subDay(), 'status' => 'cancelled']);

    expect(ScheduleEntry::query()->where('team_id', $team->id)->upcoming()->pluck('title')->all())->toBe(['Upcoming'])
        ->and(ScheduleEntry::query()->where('team_id', $team->id)->dueSoon(7)->pluck('title')->all())->toBe(['Upcoming'])
        ->and(ScheduleEntry::query()->where('team_id', $team->id)->overdue()->pluck('title')->all())->toBe(['Overdue']);
});

it('filters schedule entries by resource, territory, and status', function () {
    $team = Team::factory()->create();
    $action = app(CreateScheduleEntry::class);
    $matching = $action->handle($team->id, ['title' => 'Assigned', 'resource_key' => 'tech-1', 'territory' => 'north', 'status' => 'scheduled', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour()]);
    $action->handle($team->id, ['title' => 'Other territory', 'resource_key' => 'tech-1', 'territory' => 'south', 'starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHour()]);

    expect(ScheduleEntry::query()->where('team_id', $team->id)->forResource('tech-1')->inTerritory('north')->withStatus('scheduled')->pluck('id')->all())
        ->toBe([$matching->id]);
});

it('enforces the schedule status lifecycle', function () {
    $team = Team::factory()->create();
    $entry = app(CreateScheduleEntry::class)->handle($team->id, ['title' => 'Pump inspection', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour()]);
    $transition = app(TransitionScheduleEntry::class);

    $entry = $transition->handle($team->id, $entry, 'in_progress');
    $entry = $transition->handle($team->id, $entry, 'completed');

    expect($entry->status)->toBe('completed')
        ->and(fn () => $transition->handle($team->id, $entry, 'cancelled'))->toThrow(ValidationException::class);
});

it('does not let terminal schedule entries block future bookings', function () {
    $team = Team::factory()->create();
    $action = app(CreateScheduleEntry::class);
    $attributes = ['resource_key' => 'tech-1', 'starts_at' => '2026-08-26 09:00:00', 'ends_at' => '2026-08-26 10:00:00'];
    $completed = $action->handle($team->id, $attributes + ['title' => 'Completed visit']);
    $transition = app(TransitionScheduleEntry::class);
    $transition->handle($team->id, $completed, 'in_progress');
    $transition->handle($team->id, $completed, 'completed');

    $replacement = $action->handle($team->id, $attributes + ['title' => 'Replacement visit']);

    expect($replacement->title)->toBe('Replacement visit');
});

it('advances recurring maintenance when a schedule entry is completed', function () {
    $team = Team::factory()->create();
    $entry = app(CreateScheduleEntry::class)->handle($team->id, [
        'title' => 'Pump inspection',
        'starts_at' => now()->subHour(),
        'ends_at' => now(),
        'recurrence_type' => 'weekly',
        'recurrence_value' => 2,
    ]);

    $completed = app(TransitionScheduleEntry::class)->handle($team->id, $entry, 'in_progress');
    $completed = app(TransitionScheduleEntry::class)->handle($team->id, $completed, 'completed');

    expect($completed->last_completed_at)->not->toBeNull()
        ->and($completed->status)->toBe('scheduled')
        ->and($completed->next_due_at->equalTo($completed->last_completed_at->copy()->addWeeks(2)))->toBeTrue();
});

it('includes recurring due dates in upcoming schedules', function () {
    $team = Team::factory()->create();
    $entry = app(CreateScheduleEntry::class)->handle($team->id, [
        'title' => 'Recurring inspection',
        'starts_at' => now()->addMonths(2),
        'ends_at' => now()->addMonths(2)->addHour(),
        'next_due_at' => now()->addDays(3),
        'recurrence_type' => 'weekly',
    ]);

    expect(ScheduleEntry::query()->where('team_id', $team->id)->upcoming(7)->whereKey($entry)->exists())->toBeTrue();
});

it('supports tenant-scoped skills shifts travel and dispatch records', function () {
    $team = Team::factory()->create();
    $engineer = User::factory()->create();
    $skill = app(CreateEngineerSkill::class)->handle($team->id, ['user_id' => $engineer->id, 'name' => 'HVAC', 'proficiency' => 4]);
    $shift = app(CreateShift::class)->handle($team->id, ['user_id' => $engineer->id, 'name' => 'Day shift', 'weekday' => 1, 'starts_at' => '08:00', 'ends_at' => '16:00']);
    $entry = app(CreateScheduleEntry::class)->handle($team->id, ['title' => 'Pump visit', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour()]);
    $travel = app(CreateTravelSegment::class)->handle($team->id, $entry, ['origin' => 'Depot', 'destination' => 'Plant A', 'planned_minutes' => 45]);
    $dispatch = app(DispatchScheduleEntry::class)->handle($team->id, $entry, $engineer->id, $engineer->id, 'Bring safety kit.');

    expect($skill->team_id)->toBe($team->id)
        ->and($shift->user_id)->toBe($engineer->id)
        ->and($travel->schedule_entry_id)->toBe($entry->id)
        ->and($dispatch->status)->toBe('offered')
        ->and($entry->refresh()->travelSegments)->toHaveCount(1)
        ->and($entry->dispatches)->toHaveCount(1);
});

it('rejects duplicate engineer skills and overlapping shifts', function () {
    $team = Team::factory()->create();
    $engineer = User::factory()->create();
    $skill = app(CreateEngineerSkill::class);
    $skill->handle($team->id, ['user_id' => $engineer->id, 'name' => 'Electrical']);

    expect(fn () => $skill->handle($team->id, ['user_id' => $engineer->id, 'name' => 'Electrical']))->toThrow(ValidationException::class);

    $shift = app(CreateShift::class);
    $shift->handle($team->id, ['user_id' => $engineer->id, 'name' => 'Morning', 'weekday' => 2, 'starts_at' => '08:00', 'ends_at' => '12:00']);
    expect(fn () => $shift->handle($team->id, ['user_id' => $engineer->id, 'name' => 'Overlap', 'weekday' => 2, 'starts_at' => '11:00', 'ends_at' => '13:00']))->toThrow(ValidationException::class);
});
