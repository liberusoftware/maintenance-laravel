<?php

use Illuminate\Validation\ValidationException;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\Scheduling\Actions\CreateScheduleEntry;
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
