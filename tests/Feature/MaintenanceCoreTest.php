<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\Core\Actions\CreateOrganization;
use Liberu\Modules\Maintenance\Core\Actions\IssueNumber;
use Liberu\Modules\Maintenance\Core\Events\OrganizationCreated;
use Liberu\Modules\Maintenance\Core\Models\Organization;

it('creates an organization within its team and emits a domain event', function () {
    $team = Team::factory()->create();
    $events = [];
    Event::listen(OrganizationCreated::class, static function (OrganizationCreated $event) use (&$events): void {
        $events[] = $event;
    });

    $organization = app(CreateOrganization::class)->execute($team->id, 'North Plant', 'north-plant');

    expect($organization)->toBeInstanceOf(Organization::class)
        ->and($organization->team_id)->toBe($team->id)
        ->and($organization->code)->toBe('NORTH-PLANT')
        ->and($events)->toHaveCount(1)
        ->and($events[0]->organizationId)->toBe($organization->id);
});

it('does not allow duplicate organization codes within a team', function () {
    $team = Team::factory()->create();
    $action = app(CreateOrganization::class);
    $action->execute($team->id, 'North Plant', 'NORTH');

    expect(fn () => $action->execute($team->id, 'Another Plant', 'north'))
        ->toThrow(ValidationException::class);
});

it('issues tenant-scoped numbers atomically', function () {
    $firstTeam = Team::factory()->create();
    $secondTeam = Team::factory()->create();
    $action = app(IssueNumber::class);

    expect($action->execute($firstTeam->id, 'work-order'))->toBe('WO-000001')
        ->and($action->execute($firstTeam->id, 'work-order'))->toBe('WO-000002')
        ->and($action->execute($secondTeam->id, 'work-order'))->toBe('WO-000001');
});
