<?php

use App\Models\User;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\Core\Actions\CreateOrganization;

it('only returns organizations from the authenticated current team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $otherTeam = Team::factory()->create();
    $user->forceFill(['current_team_id' => $team->id])->save();
    $action = app(CreateOrganization::class);
    $action->execute($team->id, 'Visible', 'VISIBLE');
    $action->execute($otherTeam->id, 'Hidden', 'HIDDEN');

    $token = $user->createToken('maintenance-test')->plainTextToken;
    $response = $this->withToken($token)->getJson('/api/v1/maintenance/maintenance-core/organizations');

    $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.attributes.code', 'VISIBLE');
});

it('creates an organization using trusted current-team context', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();
    $token = $user->createToken('maintenance-test')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/v1/maintenance/maintenance-core/organizations', [
        'name' => 'API Plant',
        'code' => 'api-plant',
    ]);

    $response->assertCreated()->assertJsonPath('data.attributes.code', 'API-PLANT');
    expect($team->fresh()->id)->toBe($user->fresh()->current_team_id);
});

it('conceals an organization belonging to another team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $otherTeam = Team::factory()->create();
    $user->forceFill(['current_team_id' => $team->id])->save();
    $organization = app(CreateOrganization::class)->execute($otherTeam->id, 'Private', 'PRIVATE');
    $token = $user->createToken('maintenance-test')->plainTextToken;

    $this->withToken($token)
        ->getJson("/api/v1/maintenance/maintenance-core/organizations/{$organization->id}")
        ->assertNotFound();
});

it('updates and deletes an organization through the API within the current team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();
    $organization = app(CreateOrganization::class)->execute($team->id, 'Old Name', 'OLD');
    $token = $user->createToken('maintenance-test')->plainTextToken;

    $this->withToken($token)
        ->patchJson("/api/v1/maintenance/maintenance-core/organizations/{$organization->id}", ['name' => 'New Name', 'code' => 'NEW'])
        ->assertOk()
        ->assertJsonPath('data.attributes.name', 'New Name')
        ->assertJsonPath('data.attributes.code', 'NEW');

    $this->withToken($token)
        ->deleteJson("/api/v1/maintenance/maintenance-core/organizations/{$organization->id}")
        ->assertNoContent();
    $this->assertDatabaseMissing('maintenance_organizations', ['id' => $organization->id]);
});

it('manages statuses, priorities, and settings through tenant-scoped API endpoints', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();
    $token = $user->createToken('maintenance-test')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/maintenance/maintenance-core/statuses', [
        'name' => 'Open', 'code' => 'open', 'is_default' => true,
    ])->assertCreated()->assertJsonPath('data.attributes.code', 'OPEN');

    $this->withToken($token)->postJson('/api/v1/maintenance/maintenance-core/priorities', [
        'name' => 'Urgent', 'code' => 'urgent', 'is_default' => true,
    ])->assertCreated()->assertJsonPath('data.attributes.code', 'URGENT');

    $this->withToken($token)->postJson('/api/v1/maintenance/maintenance-core/settings', [
        'key' => 'reminder_days', 'value' => '7',
    ])->assertCreated()->assertJsonPath('data.attributes.key', 'reminder_days');

    $this->withToken($token)->getJson('/api/v1/maintenance/maintenance-core/statuses')
        ->assertOk()->assertJsonPath('data.0.attributes.code', 'OPEN');
});
