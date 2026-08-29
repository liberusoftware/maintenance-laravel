<?php

use Illuminate\Validation\ValidationException;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\Assets\Actions\CreateAsset;
use Liberu\Modules\Maintenance\Assets\Actions\RecordAssetHistory;
use Liberu\Modules\Maintenance\Assets\Actions\UpdateAsset;
use Liberu\Modules\Maintenance\Assets\Models\Asset;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

it('updates assets without allowing a tenant escape or duplicate code', function () {
    $team = Team::factory()->create();
    $other = Team::factory()->create();
    $action = app(CreateAsset::class);
    $asset = $action->handle($team->id, ['name' => 'Air Handler', 'code' => 'AH-01']);
    $otherAsset = $action->handle($team->id, ['name' => 'Pump', 'code' => 'P-01']);

    $updated = app(UpdateAsset::class)->handle($team->id, $asset, ['name' => 'Main Air Handler', 'code' => 'ah-02']);

    expect($updated->name)->toBe('Main Air Handler')->and($updated->code)->toBe('AH-02');
    expect(fn () => app(UpdateAsset::class)->handle($other->id, $asset, ['name' => 'Nope']))->toThrow(NotFoundHttpException::class);
    expect(fn () => app(UpdateAsset::class)->handle($team->id, $otherAsset, ['code' => 'ah-02']))->toThrow(ValidationException::class);
});

it('provides reusable asset status and criticality scopes', function () {
    $team = Team::factory()->create();
    $create = app(CreateAsset::class);
    $critical = $create->handle($team->id, ['name' => 'Boiler', 'code' => 'B-01', 'criticality' => 'critical']);
    $maintenance = $create->handle($team->id, ['name' => 'Pump', 'code' => 'P-01', 'status' => 'under_maintenance']);

    expect(Asset::query()->where('team_id', $team->id)->critical()->pluck('id')->all())->toBe([$critical->id])
        ->and(Asset::query()->where('team_id', $team->id)->underMaintenance()->pluck('id')->all())->toBe([$maintenance->id]);
});

it('carries sensor configuration and derives asset health', function () {
    $team = Team::factory()->create();
    $asset = app(CreateAsset::class)->handle($team->id, [
        'name' => 'Boiler', 'code' => 'B-02', 'sensor_enabled' => true,
        'sensor_type' => 'temperature', 'last_sensor_reading_at' => now(),
        'metadata' => ['sensor_status' => 'critical'],
    ]);

    expect($asset->health_status)->toBe('critical')
        ->and(Asset::query()->sensorEnabled()->withCriticalReadings()->whereKey($asset)->exists())->toBeTrue();
});

it('retains legacy equipment details on modular assets', function () {
    $team = Team::factory()->create();
    $asset = app(CreateAsset::class)->handle($team->id, [
        'name' => 'Boiler', 'code' => 'B-03', 'description' => 'Steam boiler', 'model' => 'HX-100',
        'manufacturer' => 'Acme', 'location' => 'Plant A', 'purchase_date' => '2024-01-10',
        'warranty_expiry' => '2027-01-10', 'notes' => 'Annual inspection required',
    ]);

    expect($asset->description)->toBe('Steam boiler')
        ->and($asset->model)->toBe('HX-100')
        ->and($asset->manufacturer)->toBe('Acme')
        ->and($asset->location)->toBe('Plant A')
        ->and($asset->purchase_date->toDateString())->toBe('2024-01-10')
        ->and($asset->warranty_expiry->toDateString())->toBe('2027-01-10');
});

it('exposes reusable warranty state for modular assets', function () {
    $team = Team::factory()->create();
    $covered = app(CreateAsset::class)->handle($team->id, ['name' => 'Boiler', 'code' => 'B-04', 'warranty_expiry' => now()->addDays(10)->toDateString()]);
    $expired = app(CreateAsset::class)->handle($team->id, ['name' => 'Pump', 'code' => 'P-04', 'warranty_expiry' => now()->subDay()->toDateString()]);

    $daysRemaining = $covered->warrantyDaysRemaining();

    expect($covered->isUnderWarranty())->toBeTrue()
        ->and($daysRemaining)->toBeGreaterThanOrEqual(9)
        ->and($daysRemaining)->toBeLessThanOrEqual(10)
        ->and(Asset::query()->where('team_id', $team->id)->underWarranty()->whereKey($covered)->exists())->toBeTrue()
        ->and(Asset::query()->where('team_id', $team->id)->warrantyExpired()->whereKey($expired)->exists())->toBeTrue();
});

it('records tenant-scoped asset history without replacing existing metadata', function () {
    $team = Team::factory()->create();
    $asset = app(CreateAsset::class)->handle($team->id, ['name' => 'Boiler', 'code' => 'B-05', 'metadata' => ['source' => 'import']]);

    $updated = app(RecordAssetHistory::class)->handle($team->id, $asset, 'inspection', 'Annual inspection completed.', 42);

    expect($updated->metadata['source'])->toBe('import')
        ->and($updated->metadata['history'][0]['type'])->toBe('inspection')
        ->and($updated->metadata['history'][0]['actor_id'])->toBe(42);
});
