<?php

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\LaborAndTime\Actions\ApproveTimeEntry;
use Liberu\Modules\Maintenance\LaborAndTime\Actions\CreateTimeEntry;
use Liberu\Modules\Maintenance\LaborAndTime\Actions\RejectTimeEntry;
use Liberu\Modules\Maintenance\LaborAndTime\Models\TimeEntry;

it('creates and approves a tenant-scoped time entry', function () {
    $team = Team::factory()->create();
    $engineer = User::factory()->create(['current_team_id' => $team->id]);
    $approver = User::factory()->create(['current_team_id' => $team->id]);
    $entry = app(CreateTimeEntry::class)->handle($team->id, ['user_id' => $engineer->id, 'minutes' => 90, 'description' => 'Pump repair']);
    $entry = app(ApproveTimeEntry::class)->handle($team->id, $entry, $approver->id);

    expect($entry)->toBeInstanceOf(TimeEntry::class)
        ->and($entry->team_id)->toBe($team->id)
        ->and($entry->minutes)->toBe(90)
        ->and($entry->status)->toBe('approved');
});

it('rejects zero-duration time entries', function () {
    $team = Team::factory()->create();

    expect(fn () => app(CreateTimeEntry::class)->handle($team->id, ['minutes' => 0]))
        ->toThrow(ValidationException::class);
});

it('rejects pending time entries without allowing self-rejection', function () {
    $team = Team::factory()->create();
    $engineer = User::factory()->create(['current_team_id' => $team->id]);
    $approver = User::factory()->create(['current_team_id' => $team->id]);
    $entry = app(CreateTimeEntry::class)->handle($team->id, ['user_id' => $engineer->id, 'minutes' => 60, 'description' => 'Pump repair']);

    $rejected = app(RejectTimeEntry::class)->handle($team->id, $entry, $approver->id, 'Missing work order');

    expect($rejected->status)->toBe('rejected')
        ->and($rejected->description)->toContain('Missing work order');
    expect(fn () => app(RejectTimeEntry::class)->handle($team->id, $rejected, $approver->id))
        ->toThrow(ValidationException::class);
});
