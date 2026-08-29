<?php

use App\Models\User;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\Core\Actions\CreateOrganization;
use Liberu\Modules\Maintenance\Core\Livewire\Components\OrganizationList;
use Livewire\Livewire;

it('renders only organizations from the current team in Livewire', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();
    app(CreateOrganization::class)->execute($team->id, 'Livewire Plant', 'LW');

    $this->actingAs($user);

    Livewire::test(OrganizationList::class)
        ->assertSee('Livewire Plant')
        ->assertSee('LW');
});

it('creates an organization through the Livewire public action', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();
    $this->actingAs($user);

    Livewire::test(OrganizationList::class)
        ->set('name', 'Created Plant')
        ->set('code', 'created')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('maintenance_organizations', [
        'team_id' => $team->id,
        'code' => 'CREATED',
    ]);
});
