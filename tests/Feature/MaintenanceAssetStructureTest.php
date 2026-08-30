<?php

use App\Models\User;
use Liberu\Foundation\Organizations\Models\Team;

it('exposes tenant-scoped asset hierarchy categories specifications warranties and history', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();
    $token = $user->createToken('asset-structure-test')->plainTextToken;

    $category = $this->withToken($token)->postJson('/api/v1/maintenance/assets/categories', [
        'name' => 'Pumps',
        'code' => 'pumps',
    ])->assertCreated()->json('data.id');
    $parent = $this->withToken($token)->postJson('/api/v1/maintenance/assets', [
        'name' => 'Plant pump assembly',
        'code' => 'pump-assembly',
        'category_id' => $category,
    ])->assertCreated()->json('data.id');
    $asset = $this->withToken($token)->postJson('/api/v1/maintenance/assets', [
        'name' => 'Primary pump motor',
        'code' => 'pump-motor',
        'parent_id' => $parent,
        'category_id' => $category,
    ])->assertCreated()->json('data.id');

    $this->withToken($token)->postJson("/api/v1/maintenance/assets/{$asset}/specifications", [
        'key' => 'voltage',
        'value' => '480',
        'unit' => 'V',
    ])->assertCreated();
    $this->withToken($token)->postJson("/api/v1/maintenance/assets/{$asset}/warranties", [
        'provider' => 'Acme',
        'expires_on' => now()->addYear()->toDateString(),
    ])->assertCreated();
    $this->withToken($token)->postJson("/api/v1/maintenance/assets/{$asset}/history", [
        'type' => 'commissioned',
        'note' => 'Installed in plant A.',
    ])->assertOk();

    $this->withToken($token)->getJson('/api/v1/maintenance/assets/categories')->assertOk()->assertJsonCount(1, 'data');
    $this->withToken($token)->getJson("/api/v1/maintenance/assets/{$asset}/specifications")->assertOk()->assertJsonPath('data.0.attributes.key', 'voltage');
    $this->withToken($token)->getJson("/api/v1/maintenance/assets/{$asset}/warranties")->assertOk()->assertJsonPath('data.0.attributes.provider', 'Acme');
    $this->withToken($token)->getJson("/api/v1/maintenance/assets/{$asset}/history")->assertOk()->assertJsonPath('data.0.attributes.type', 'commissioned');
});

it('rejects asset hierarchy references from another tenant', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $otherTeam = Team::factory()->create();
    $user->forceFill(['current_team_id' => $team->id])->save();
    $otherAsset = \Liberu\Modules\Maintenance\Assets\Actions\CreateAsset::class;
    $foreign = app($otherAsset)->handle($otherTeam->id, ['name' => 'Foreign asset', 'code' => 'foreign']);
    $token = $user->createToken('asset-structure-isolation-test')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/maintenance/assets', [
        'name' => 'Local asset',
        'code' => 'local',
        'parent_id' => $foreign->id,
    ])->assertUnprocessable();
});
