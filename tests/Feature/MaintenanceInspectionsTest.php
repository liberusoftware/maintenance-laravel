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
