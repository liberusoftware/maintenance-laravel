<?php

use Illuminate\Validation\ValidationException;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\Commercial\Actions\CreateCommercialRecord;
use Liberu\Modules\Maintenance\Commercial\Actions\TransitionCommercialRecord;
use Liberu\Modules\Maintenance\Commercial\Models\CommercialRecord;
use Liberu\Modules\Maintenance\Compliance\Actions\CreateComplianceRecord;
use Liberu\Modules\Maintenance\Compliance\Models\ComplianceRecord;
use Liberu\Modules\Maintenance\Portal\Actions\CreatePortalRecord;
use Liberu\Modules\Maintenance\Portal\Actions\TransitionPortalRecord;
use Liberu\Modules\Maintenance\Portal\Models\PortalRecord;
use Liberu\Modules\Maintenance\Report\Actions\CreateReportRecord;
use Liberu\Modules\Maintenance\Report\Actions\PublishReport;
use Liberu\Modules\Maintenance\Report\Actions\UpdateReportRecord;
use Liberu\Modules\Maintenance\Report\Models\ReportKind;
use Liberu\Modules\Maintenance\Report\Models\ReportRecord;
use Liberu\Modules\Maintenance\Report\Queries\BuildReportSummary;

it('creates tenant-scoped records for the remaining maintenance capabilities', function () {
    $team = Team::factory()->create();

    $commercial = app(CreateCommercialRecord::class)->handle($team->id, ['kind' => 'quote', 'title' => 'Annual contract', 'amount' => 1200]);
    $compliance = app(CreateComplianceRecord::class)->handle($team->id, ['kind' => 'permit', 'title' => 'Boiler permit']);
    $portal = app(CreatePortalRecord::class)->handle($team->id, ['kind' => 'request', 'title' => 'Request a visit']);
    $report = app(CreateReportRecord::class)->handle($team->id, ['kind' => 'backlog', 'title' => 'Open work orders', 'metric_value' => 4]);

    expect($commercial)->toBeInstanceOf(CommercialRecord::class)
        ->and($compliance)->toBeInstanceOf(ComplianceRecord::class)
        ->and($portal)->toBeInstanceOf(PortalRecord::class)
        ->and($report)->toBeInstanceOf(ReportRecord::class)
        ->and($commercial->team_id)->toBe($team->id)
        ->and($report->metric_value)->toBe('4.00');
});

it('rejects incomplete remaining capability records', function () {
    $team = Team::factory()->create();

    expect(fn () => app(CreateCommercialRecord::class)->handle($team->id, ['kind' => 'quote']))
        ->toThrow(ValidationException::class);
});

it('restricts reporting records to the supported metric families', function () {
    $team = Team::factory()->create();

    expect(ReportKind::options())->toHaveKeys(['backlog', 'response', 'first_time_fix', 'downtime', 'cost', 'utilization', 'stock', 'sla', 'compliance'])
        ->and(fn () => app(CreateReportRecord::class)->handle($team->id, ['kind' => 'arbitrary', 'title' => 'Unsupported metric']))
        ->toThrow(ValidationException::class);
});

it('filters reporting records by kind and overlapping period', function () {
    $team = Team::factory()->create();
    $create = app(CreateReportRecord::class);
    $backlog = $create->handle($team->id, [
        'kind' => 'backlog', 'title' => 'August backlog',
        'period_start' => '2026-08-01 00:00:00', 'period_end' => '2026-08-31 23:59:59',
    ]);
    $create->handle($team->id, [
        'kind' => 'cost', 'title' => 'July costs',
        'period_start' => '2026-07-01 00:00:00', 'period_end' => '2026-07-31 23:59:59',
    ]);

    expect(ReportRecord::query()
        ->where('team_id', $team->id)->ofKind('backlog')->forPeriod('2026-08-15', '2026-08-20')->pluck('id')->all())
        ->toBe([$backlog->id]);
});

it('publishes a draft reporting record through its domain action', function () {
    $team = Team::factory()->create();
    $record = app(CreateReportRecord::class)->handle($team->id, ['kind' => 'backlog', 'title' => 'August backlog']);

    $published = app(PublishReport::class)->execute($team->id, $record);

    expect($published->status)->toBe('published')
        ->and(ReportRecord::query()->published()->whereKey($record->id)->exists())->toBeTrue();
});

it('requires the publish action for report status changes', function () {
    $team = Team::factory()->create();
    $record = app(CreateReportRecord::class)->handle($team->id, ['kind' => 'backlog', 'title' => 'August backlog']);

    expect(fn () => app(UpdateReportRecord::class)->handle($team->id, $record, ['status' => 'published']))
        ->toThrow(ValidationException::class);
});

it('builds a tenant-scoped reporting summary by kind and status', function () {
    $team = Team::factory()->create();
    $create = app(CreateReportRecord::class);
    $create->handle($team->id, ['kind' => 'backlog', 'title' => 'Open', 'metric_value' => 4]);
    $published = $create->handle($team->id, ['kind' => 'backlog', 'title' => 'Closed', 'metric_value' => 2]);
    app(PublishReport::class)->execute($team->id, $published);
    $otherTeam = Team::factory()->create();
    $create->handle($otherTeam->id, ['kind' => 'backlog', 'title' => 'Other tenant', 'metric_value' => 100]);

    $summary = app(BuildReportSummary::class)->handle($team->id);

    expect($summary['total_records'])->toBe(2)
        ->and($summary['published_records'])->toBe(1)
        ->and($summary['draft_records'])->toBe(1)
        ->and($summary['metric_total'])->toBe(6.0)
        ->and($summary['by_kind']['backlog'])->toBe(['count' => 2, 'metric_total' => 6.0]);
});

it('scopes compliance records by expiry', function () {
    $team = Team::factory()->create();
    $create = app(CreateComplianceRecord::class);
    $expired = $create->handle($team->id, ['kind' => 'permit', 'title' => 'Expired permit', 'expires_at' => '2026-01-01']);
    $current = $create->handle($team->id, ['kind' => 'permit', 'title' => 'Current permit', 'expires_at' => '2027-01-01']);

    expect(ComplianceRecord::query()->where('team_id', $team->id)->expired()->whereKey($expired)->exists())->toBeTrue()
        ->and(ComplianceRecord::query()->where('team_id', $team->id)->current()->whereKey($current)->exists())->toBeTrue();
});

it('enforces portal request status transitions', function () {
    $team = Team::factory()->create();
    $record = app(CreatePortalRecord::class)->handle($team->id, ['kind' => 'request', 'title' => 'Visit request']);
    $transition = app(TransitionPortalRecord::class);

    $submitted = $transition->handle($team->id, $record, 'submitted');
    expect($submitted->status)->toBe('submitted');
    expect(fn () => $transition->handle($team->id, $submitted, 'resolved'))->toThrow(ValidationException::class);
});

it('enforces commercial record status transitions', function () {
    $team = Team::factory()->create();
    $record = app(CreateCommercialRecord::class)->handle($team->id, ['kind' => 'quote', 'title' => 'Annual contract']);
    $transition = app(TransitionCommercialRecord::class);

    $proposed = $transition->handle($team->id, $record, 'proposed');
    expect($proposed->status)->toBe('proposed');
    expect(fn () => $transition->handle($team->id, $proposed, 'fulfilled'))->toThrow(ValidationException::class);
});
