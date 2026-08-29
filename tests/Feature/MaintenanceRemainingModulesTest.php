<?php

use Illuminate\Validation\ValidationException;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\Commercial\Actions\CreateCommercialRecord;
use Liberu\Modules\Maintenance\Commercial\Models\CommercialRecord;
use Liberu\Modules\Maintenance\Compliance\Actions\CreateComplianceRecord;
use Liberu\Modules\Maintenance\Compliance\Models\ComplianceRecord;
use Liberu\Modules\Maintenance\Portal\Actions\CreatePortalRecord;
use Liberu\Modules\Maintenance\Portal\Models\PortalRecord;
use Liberu\Modules\Maintenance\Report\Actions\CreateReportRecord;
use Liberu\Modules\Maintenance\Report\Models\ReportRecord;

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
