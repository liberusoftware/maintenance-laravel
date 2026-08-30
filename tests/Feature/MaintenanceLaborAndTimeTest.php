<?php

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\LaborAndTime\Actions\ApproveTimeEntry;
use Liberu\Modules\Maintenance\LaborAndTime\Actions\CreateTimeEntry;
use Liberu\Modules\Maintenance\LaborAndTime\Actions\CreateAttendance;
use Liberu\Modules\Maintenance\LaborAndTime\Actions\CreateEngineerSkill;
use Liberu\Modules\Maintenance\LaborAndTime\Actions\CreateExpense;
use Liberu\Modules\Maintenance\LaborAndTime\Actions\CreateLaborRate;
use Liberu\Modules\Maintenance\LaborAndTime\Actions\RejectTimeEntry;
use Liberu\Modules\Maintenance\LaborAndTime\Models\TimeEntry;

it('publishes labor records through the tenant-scoped API', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();
    $token = $user->createToken('labor-api-test')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/maintenance/labor-and-time/skills', [
        'user_id' => $user->id,
        'skill' => 'HVAC',
        'level' => 4,
    ])->assertCreated()->assertJsonPath('data.attributes.skill', 'HVAC');

    $this->withToken($token)->postJson('/api/v1/maintenance/labor-and-time/attendance', [
        'user_id' => $user->id,
        'attendance_date' => '2026-08-30',
        'clocked_in_at' => '2026-08-30 08:00:00',
        'clocked_out_at' => '2026-08-30 16:00:00',
    ])->assertCreated()->assertJsonPath('data.attributes.duration_minutes', 480);

    $this->withToken($token)->postJson('/api/v1/maintenance/labor-and-time/rates', [
        'name' => 'Standard technician',
        'hourly_rate' => 52.5,
        'effective_from' => '2026-08-30',
    ])->assertCreated()->assertJsonPath('data.attributes.hourly_rate', '52.50');

    $this->withToken($token)->postJson('/api/v1/maintenance/labor-and-time/expenses', [
        'description' => 'Parking',
        'amount' => 18.25,
    ])->assertCreated()->assertJsonPath('data.attributes.status', 'pending');

    $this->withToken($token)->getJson('/api/v1/maintenance/labor-and-time/skills')
        ->assertOk()->assertJsonCount(1, 'data');
});

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

it('provides tenant-scoped skills, attendance, labor rates, and expenses', function () {
    $team = Team::factory()->create();
    $user = \App\Models\User::factory()->create();

    $skill = app(CreateEngineerSkill::class)->handle($team->id, ['user_id' => $user->id, 'skill' => 'HVAC', 'level' => 4]);
    $attendance = app(CreateAttendance::class)->handle($team->id, ['user_id' => $user->id, 'attendance_date' => today(), 'clocked_in_at' => now()->subHours(8), 'clocked_out_at' => now()]);
    $rate = app(CreateLaborRate::class)->handle($team->id, ['user_id' => $user->id, 'name' => 'Standard technician', 'hourly_rate' => 52.5, 'effective_from' => today()]);
    $expense = app(CreateExpense::class)->handle($team->id, ['user_id' => $user->id, 'description' => 'Parking', 'amount' => 18.25]);

    expect($skill->isCertified())->toBeTrue()
        ->and($attendance->durationMinutes())->toBe(480)
        ->and($rate->hourly_rate)->toBe('52.50')
        ->and($rate->isActive())->toBeTrue()
        ->and($expense->status)->toBe('pending')
        ->and($skill->team_id)->toBe($team->id)
        ->and($expense->team_id)->toBe($team->id);
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

it('enforces time ranges and exposes status scopes', function () {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->id]);
    expect(fn () => app(CreateTimeEntry::class)->handle($team->id, ['minutes' => 60, 'started_at' => '2026-08-29 10:00:00', 'ended_at' => '2026-08-29 09:00:00']))
        ->toThrow(ValidationException::class);

    $entry = app(CreateTimeEntry::class)->handle($team->id, ['minutes' => 60, 'user_id' => $user->id]);
    expect(TimeEntry::query()->where('team_id', $team->id)->pending()->forUser((int) $entry->user_id)->whereKey($entry)->exists())->toBeTrue();
});
