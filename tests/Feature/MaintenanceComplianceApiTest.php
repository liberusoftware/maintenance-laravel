<?php

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\Compliance\Actions\CreateComplianceIncident;
use Liberu\Modules\Maintenance\Compliance\Actions\CreateCompliancePermit;
use Liberu\Modules\Maintenance\Compliance\Actions\CreateComplianceRecord;
use Liberu\Modules\Maintenance\Compliance\Actions\CreateComplianceRequirement;
use Liberu\Modules\Maintenance\Compliance\Actions\CreateComplianceRiskAssessment;
use Liberu\Modules\Maintenance\Compliance\Models\ComplianceIncident;
use Liberu\Modules\Maintenance\Compliance\Models\CompliancePermit;
use Liberu\Modules\Maintenance\Compliance\Models\ComplianceRequirement;
use Liberu\Modules\Maintenance\Compliance\Models\ComplianceRiskAssessment;

it('creates and filters tenant-scoped compliance records through the API', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $otherTeam = Team::factory()->create();
    $user->forceFill(['current_team_id' => $team->id])->save();
    app(CreateComplianceRecord::class)->handle($team->id, ['kind' => 'permit', 'title' => 'Boiler permit', 'description' => 'Annual permit', 'expires_at' => now()->addDay(), 'metadata' => ['issuer' => 'City']]);
    app(CreateComplianceRecord::class)->handle($otherTeam->id, ['kind' => 'permit', 'title' => 'Hidden permit']);
    $token = $user->createToken('maintenance-compliance-test')->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/maintenance/compliance?current=1')
        ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.attributes.title', 'Boiler permit')->assertJsonPath('data.0.attributes.metadata.issuer', 'City');
});

it('exposes compliance requirements permits risks and incidents through the API', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();
    $token = $user->createToken('compliance-capabilities-test')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/maintenance/compliance/requirements', [
        'code' => 'OSHA-01', 'title' => 'Lockout procedure', 'expires_at' => now()->addYear(),
    ])->assertCreated()->assertJsonPath('data.attributes.code', 'OSHA-01');
    $this->withToken($token)->postJson('/api/v1/maintenance/compliance/permits', [
        'number' => 'P-100', 'title' => 'Boiler permit', 'issued_at' => now()->subDay(), 'expires_at' => now()->addYear(),
    ])->assertCreated()->assertJsonPath('data.attributes.number', 'P-100');
    $this->withToken($token)->postJson('/api/v1/maintenance/compliance/risk-assessments', [
        'title' => 'Chemical exposure', 'severity' => 'high', 'score' => 80,
    ])->assertCreated()->assertJsonPath('data.attributes.score', 80);
    $this->withToken($token)->postJson('/api/v1/maintenance/compliance/incidents', [
        'title' => 'Near miss', 'severity' => 'medium', 'occurred_at' => now()->subHour(),
    ])->assertCreated()->assertJsonPath('data.attributes.title', 'Near miss');

    $this->withToken($token)->getJson('/api/v1/maintenance/compliance/requirements')
        ->assertOk()->assertJsonCount(1, 'data');
    $this->withToken($token)->getJson('/api/v1/maintenance/compliance/permits')
        ->assertOk()->assertJsonCount(1, 'data');
    $this->withToken($token)->getJson('/api/v1/maintenance/compliance/risk-assessments')
        ->assertOk()->assertJsonCount(1, 'data');
    $this->withToken($token)->getJson('/api/v1/maintenance/compliance/incidents')
        ->assertOk()->assertJsonCount(1, 'data');
});

it('keeps compliance capability records tenant scoped in actions', function () {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();

    $requirement = app(CreateComplianceRequirement::class)->handle($team->id, ['code' => 'REQ-1', 'title' => 'Requirement']);
    $permit = app(CreateCompliancePermit::class)->handle($team->id, ['number' => 'PER-1', 'title' => 'Permit']);
    $risk = app(CreateComplianceRiskAssessment::class)->handle($team->id, ['title' => 'Risk', 'score' => 50]);
    $incident = app(CreateComplianceIncident::class)->handle($team->id, ['title' => 'Incident']);

    expect($requirement)->toBeInstanceOf(ComplianceRequirement::class)
        ->and($permit)->toBeInstanceOf(CompliancePermit::class)
        ->and($risk)->toBeInstanceOf(ComplianceRiskAssessment::class)
        ->and($incident)->toBeInstanceOf(ComplianceIncident::class)
        ->and($requirement->team_id)->toBe($team->id)
        ->and($permit->team_id)->not->toBe($otherTeam->id)
        ->and($risk->team_id)->toBe($team->id)
        ->and($incident->team_id)->toBe($team->id);

    expect(fn () => app(CreateComplianceRequirement::class)->handle($team->id, ['code' => 'REQ-1', 'title' => 'Duplicate']))
        ->toThrow(ValidationException::class);
    expect(ComplianceRequirement::query()->where('team_id', $otherTeam->id)->count())->toBe(0);
});
