<?php

use Illuminate\Validation\ValidationException;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\Scheduling\Actions\CreateScheduleEntry;
use Liberu\Modules\Maintenance\Scheduling\Actions\TransitionScheduleEntry;
use Liberu\Modules\Maintenance\Scheduling\Models\ScheduleEntry;

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
        ->and($completed->next_due_at->equalTo($completed->last_completed_at->copy()->addWeeks(2)))->toBeTrue();
});
