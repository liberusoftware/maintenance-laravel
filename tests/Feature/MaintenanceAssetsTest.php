<?php

use Illuminate\Validation\ValidationException;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\Assets\Actions\CreateAsset;
use Liberu\Modules\Maintenance\Assets\Models\Asset;

it('creates tenant-scoped assets with normalized identifiers', function () {
    $team = Team::factory()->create();
    $asset = app(CreateAsset::class)->handle($team->id, ['name' => 'Air Handler', 'code' => 'ah-01', 'category' => 'HVAC']);

    expect($asset)->toBeInstanceOf(Asset::class)
        ->and($asset->team_id)->toBe($team->id)
        ->and($asset->code)->toBe('AH-01')
        ->and($asset->status)->toBe('active');
});

it('rejects duplicate asset codes within a team', function () {
    $team = Team::factory()->create();
    $action = app(CreateAsset::class);
    $action->handle($team->id, ['name' => 'Air Handler', 'code' => 'AH-01']);

    expect(fn () => $action->handle($team->id, ['name' => 'Other', 'code' => 'ah-01']))
        ->toThrow(ValidationException::class);
});
