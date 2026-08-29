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
