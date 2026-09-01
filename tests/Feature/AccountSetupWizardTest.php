<?php

use App\Filament\Pages\AccountSetupWizard;
use App\Models\User;
use Illuminate\Support\Carbon;
use Liberu\Foundation\Organizations\Models\Team;
use Livewire\Livewire;

it('shows the setup guide to an account that has not completed onboarding', function () {
    $user = User::factory()->create(['onboarding_completed_at' => null]);
    $team = Team::factory()->create(['user_id' => $user->id, 'name' => 'New workspace']);
    $user->forceFill(['current_team_id' => $team->id])->save();

    $this->actingAs($user);

    Livewire::test(AccountSetupWizard::class)
        ->assertOk()
        ->assertFormSet([
            'team_name' => 'New workspace',
            'timezone' => 'UTC',
            'workflow' => 'maintenance',
        ]);
});

it('saves workspace settings and marks onboarding complete', function () {
    $user = User::factory()->create(['onboarding_completed_at' => null]);
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();

    $this->actingAs($user);

    Livewire::test(AccountSetupWizard::class)
        ->fillForm([
            'team_name' => 'Field Services',
            'timezone' => 'Europe/London',
            'workflow' => 'service',
            'oauth_provider' => 'google',
            'oauth_client_id' => 'client-id',
            'oauth_client_secret' => 'client-secret',
            'api_key' => 'service-key',
        ])
        ->call('save');

    expect($team->refresh()->name)->toBe('Field Services')
        ->and($team->settings)->toMatchArray([
            'timezone' => 'Europe/London',
            'workflow' => 'service',
            'oauth_provider' => 'google',
            'oauth_client_id' => 'client-id',
            'oauth_client_secret' => 'client-secret',
            'api_key' => 'service-key',
        ])
        ->and($user->refresh()->onboarding_completed_at)->toBeInstanceOf(Carbon::class);
});
