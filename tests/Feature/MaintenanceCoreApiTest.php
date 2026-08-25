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
