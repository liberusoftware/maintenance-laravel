<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\Core\Actions\ConfigureNumbering;
use Liberu\Modules\Maintenance\Core\Actions\CreateOrganization;
use Liberu\Modules\Maintenance\Core\Actions\CreatePriority;
use Liberu\Modules\Maintenance\Core\Actions\CreateStatus;
use Liberu\Modules\Maintenance\Core\Actions\DeleteOrganization;
use Liberu\Modules\Maintenance\Core\Actions\IssueNumber;
use Liberu\Modules\Maintenance\Core\Actions\SetServiceSetting;
use Liberu\Modules\Maintenance\Core\Actions\UpdateOrganization;
use Liberu\Modules\Maintenance\Core\Events\OrganizationCreated;
use Liberu\Modules\Maintenance\Core\Events\OrganizationDeleted;
use Liberu\Modules\Maintenance\Core\Events\OrganizationUpdated;
use Liberu\Modules\Maintenance\Core\Models\Organization;
use Liberu\Modules\Maintenance\Core\Models\Priority;
use Liberu\Modules\Maintenance\Core\Models\Status;

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

it('updates and deletes an organization through explicit actions and events', function () {
    $team = Team::factory()->create();
    $organization = app(CreateOrganization::class)->execute($team->id, 'North Plant', 'NORTH');
    $events = [];
    Event::listen(OrganizationUpdated::class, static function (OrganizationUpdated $event) use (&$events): void {
        $events[] = $event;
    });
    Event::listen(OrganizationDeleted::class, static function (OrganizationDeleted $event) use (&$events): void {
        $events[] = $event;
    });

    $updated = app(UpdateOrganization::class)->execute($organization, ['name' => 'South Plant', 'code' => 'south']);
    expect($updated->code)->toBe('SOUTH');
    app(DeleteOrganization::class)->execute($updated);

    expect($events)->toHaveCount(2)
        ->and($events[0]->organizationId)->toBe($organization->id)
        ->and($events[1]->teamId)->toBe($team->id);
    $this->assertDatabaseMissing('maintenance_organizations', ['id' => $organization->id]);
});

it('keeps statuses and priorities tenant scoped with one default each', function () {
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    $statusAction = app(CreateStatus::class);
    $priorityAction = app(CreatePriority::class);
    $statusAction->execute($team->id, ['name' => 'Open', 'code' => 'open', 'is_default' => true]);
    $secondStatus = $statusAction->execute($team->id, ['name' => 'Closed', 'code' => 'closed', 'is_default' => true]);
    $priorityAction->execute($team->id, ['name' => 'Low', 'code' => 'low', 'is_default' => true]);
    $priorityAction->execute($otherTeam->id, ['name' => 'Low', 'code' => 'low', 'is_default' => true]);

    expect($secondStatus->fresh()->is_default)->toBeTrue()
        ->and(Status::query()->where('team_id', $team->id)->where('is_default', true)->count())->toBe(1)
        ->and(Priority::query()->where('team_id', $otherTeam->id)->count())->toBe(1);
});

it('upserts settings and numbering configuration per team', function () {
    $team = Team::factory()->create();
    $setting = app(SetServiceSetting::class)->execute($team->id, 'reminder_days', '7');
    app(SetServiceSetting::class)->execute($team->id, 'reminder_days', '14');
    $sequence = app(ConfigureNumbering::class)->execute($team->id, 'work-order', 'WO-', 8);

    expect($setting->fresh()->value)->toBe('14')
        ->and($sequence->prefix)->toBe('WO-')
        ->and($sequence->padding)->toBe(8);
});

it('provides lifecycle scopes for core configuration records', function () {
    $team = Team::factory()->create();
    $organization = app(CreateOrganization::class)->execute($team->id, 'North Plant', 'NORTH');
    $inactive = app(UpdateOrganization::class)->execute($organization, ['state' => 'inactive']);
    app(CreateStatus::class)->execute($team->id, ['name' => 'Open', 'code' => 'open', 'is_active' => true]);
    app(CreateStatus::class)->execute($team->id, ['name' => 'Closed', 'code' => 'closed', 'is_active' => false]);

    expect(Organization::query()->inactive()->whereKey($inactive)->exists())->toBeTrue()
        ->and(Status::query()->where('team_id', $team->id)->active()->count())->toBe(1)
        ->and(Status::query()->where('team_id', $team->id)->inactive()->count())->toBe(1);
});
