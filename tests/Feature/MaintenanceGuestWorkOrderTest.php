<?php

use Illuminate\Support\Facades\Config;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrder;

it('accepts a configured, rate-limited public maintenance request', function () {
    $team = Team::factory()->create();
    Config::set('maintenance-work-orders.public_team_id', $team->id);

    $response = $this->postJson('/api/v1/maintenance/work-orders/public', [
        'title' => 'Leaking faucet',
        'description' => 'Water is pooling below the sink.',
        'priority' => 'high',
        'location' => 'Building A',
        'guest_name' => 'Jane Doe',
        'guest_email' => 'jane@example.com',
        'equipment' => 'Kitchen sink',
    ]);

    $response->assertCreated()->assertJsonPath('data.attributes.status', 'requested');
    expect(WorkOrder::query()->where('team_id', $team->id)->first()->metadata)->toMatchArray(['equipment' => 'Kitchen sink']);
});

it('does not expose public intake until a team is configured', function () {
    Config::set('maintenance-work-orders.public_team_id', null);

    $this->postJson('/api/v1/maintenance/work-orders/public', [])->assertServiceUnavailable();
});
