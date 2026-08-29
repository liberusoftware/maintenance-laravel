<?php

use Illuminate\Validation\ValidationException;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\Inspections\Actions\CompleteInspection;
use Liberu\Modules\Maintenance\Inspections\Actions\CreateInspection;
use Liberu\Modules\Maintenance\Inspections\Models\Inspection;

it('creates and completes a tenant-scoped inspection', function () {
    $team = Team::factory()->create();
    $inspection = app(CreateInspection::class)->handle($team->id, ['title' => 'Pump safety check']);
    $inspection = app(CompleteInspection::class)->handle($team->id, $inspection, 'pass');

    expect($inspection)->toBeInstanceOf(Inspection::class)
        ->and($inspection->team_id)->toBe($team->id)
        ->and($inspection->status)->toBe('completed')
        ->and($inspection->outcome)->toBe('pass');
});

it('rejects an invalid inspection outcome', function () {
    $team = Team::factory()->create();
    $inspection = app(CreateInspection::class)->handle($team->id, ['title' => 'Pump safety check']);

    expect(fn () => app(CompleteInspection::class)->handle($team->id, $inspection, 'unknown'))
        ->toThrow(ValidationException::class);
});

it('does not allow a completed inspection to be completed again', function () {
    $team = Team::factory()->create();
    $inspection = app(CreateInspection::class)->handle($team->id, ['title' => 'Pump safety check']);
    $inspection = app(CompleteInspection::class)->handle($team->id, $inspection, 'pass');

    expect(fn () => app(CompleteInspection::class)->handle($team->id, $inspection, 'fail'))
        ->toThrow(ValidationException::class);
});

it('provides inspection status, outcome, and date query scopes', function () {
    $team = Team::factory()->create();
    $create = app(CreateInspection::class);
    $draft = $create->handle($team->id, ['title' => 'Draft inspection']);
    $passed = $create->handle($team->id, ['title' => 'Passed inspection', 'inspected_at' => '2026-08-20 10:00:00']);
    app(CompleteInspection::class)->handle($team->id, $passed, 'pass');

    expect(Inspection::query()->where('team_id', $team->id)->draft()->whereKey($draft)->exists())->toBeTrue()
        ->and(Inspection::query()->where('team_id', $team->id)->completed()->withOutcome('pass')->inspectedBetween('2026-08-20', '2026-08-21')->whereKey($passed)->exists())->toBeTrue();
});
