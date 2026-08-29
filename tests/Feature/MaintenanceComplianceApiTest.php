<?php

use App\Models\User;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\Compliance\Actions\CreateComplianceRecord;

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
