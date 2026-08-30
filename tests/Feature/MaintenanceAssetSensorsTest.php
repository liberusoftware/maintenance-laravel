<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\Assets\Actions\CreateAsset;
use Liberu\Modules\Maintenance\Assets\Actions\CreateAssetMeter;
use Liberu\Modules\Maintenance\Assets\Actions\RecordAssetMeterReading;
use Liberu\Modules\Maintenance\Assets\Models\AssetMeter;
use Liberu\Modules\Maintenance\Assets\Models\SensorReading;
use Liberu\Modules\Maintenance\WorkOrders\Actions\CreateWorkOrder;

uses(RefreshDatabase::class);

it('records sensor readings and derives threshold status in the assets module', function () {
    $team = Team::factory()->create();
    $asset = app(CreateAsset::class)->handle($team->id, [
        'name' => 'Boiler',
        'code' => 'B-100',
        'sensor_enabled' => true,
        'sensor_id' => 'SENSOR-100',
        'sensor_config' => ['thresholds' => ['temperature' => ['critical_max' => 90]]],
    ]);

    $response = $this->postJson('/api/iot-sensors/readings', [
        'sensor_id' => 'SENSOR-100',
        'metric_name' => 'temperature',
        'value' => 95,
        'unit' => 'C',
    ]);

    $response->assertCreated()->assertJsonPath('data.status', 'critical');
    expect(SensorReading::query()->where('asset_id', $asset->id)->value('status'))->toBe('critical')
        ->and($asset->fresh()->health_status)->toBe('critical');
});

it('keeps sensor dashboards tenant scoped and protects asset health endpoints', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();
    app(CreateAsset::class)->handle($team->id, ['name' => 'Pump', 'code' => 'P-100', 'sensor_enabled' => true, 'sensor_id' => 'SENSOR-101']);

    $token = $user->createToken('sensor-test')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/iot-sensors/dashboard')
        ->assertOk()
        ->assertJsonPath('data.total_monitored', 1)
        ->assertJsonPath('data.no_data', 1);
});

it('supports partial failures when ingesting sensor batches', function () {
    $team = Team::factory()->create();
    app(CreateAsset::class)->handle($team->id, ['name' => 'Boiler', 'code' => 'B-101', 'sensor_enabled' => true, 'sensor_id' => 'SENSOR-102']);
    app(CreateAsset::class)->handle($team->id, ['name' => 'Pump', 'code' => 'P-101', 'sensor_enabled' => false, 'sensor_id' => 'SENSOR-103']);

    $this->postJson('/api/iot-sensors/readings/batch', ['readings' => [
        ['sensor_id' => 'SENSOR-102', 'metric_name' => 'temperature', 'value' => 70],
        ['sensor_id' => 'SENSOR-103', 'metric_name' => 'temperature', 'value' => 70],
    ]])->assertOk()->assertJson(['stored_count' => 1, 'errors_count' => 1]);
});

it('supports tenant-scoped asset meters and updates their latest reading', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();
    $asset = app(CreateAsset::class)->handle($team->id, ['name' => 'Boiler', 'code' => 'B-102']);
    $meter = app(CreateAssetMeter::class)->handle($team->id, $asset, ['name' => 'Operating hours', 'unit' => 'hours']);
    $reading = app(RecordAssetMeterReading::class)->handle($team->id, $asset, $meter, 125.5, '2026-08-30 12:00:00', $user->id, 'Weekly inspection');

    expect($meter)->toBeInstanceOf(AssetMeter::class)
        ->and($reading->value)->toBe(125.5)
        ->and($meter->refresh()->current_value)->toBe(125.5)
        ->and($asset->refresh()->meters()->count())->toBe(1);

    $token = $user->createToken('asset-meter-test')->plainTextToken;
    $this->withToken($token)->getJson("/api/v1/maintenance/assets/{$asset->id}/meters")
        ->assertOk()->assertJsonPath('data.0.attributes.current_value', 125.5);
});

it('derives asset health from recent sensor readings and exposes legacy work-order status helpers', function () {
    $team = Team::factory()->create();
    $asset = app(CreateAsset::class)->handle($team->id, ['name' => 'Compressor', 'code' => 'C-100', 'sensor_enabled' => true, 'sensor_id' => 'SENSOR-200']);
    SensorReading::query()->create(['asset_id' => $asset->id, 'metric_name' => 'temperature', 'value' => 95, 'status' => 'critical', 'reading_at' => now()]);

    expect($asset->fresh()->health_status)->toBe('critical')
        ->and($asset->fresh()->recentSensorReadings)->toHaveCount(1)
        ->and($asset->fresh()->criticalSensorReadings)->toHaveCount(1);

    $workOrder = app(CreateWorkOrder::class)->handle($team->id, ['title' => 'Repair compressor', 'equipment_id' => $asset->id]);
    $asset->refresh();
    expect($asset->hasActiveWorkOrders())->toBeTrue()
        ->and($asset->canBeSetToActive())->toBeFalse();

    $asset->syncStatusWithWorkOrders();
    expect($asset->refresh()->status)->toBe('under_maintenance');
    $workOrder->update(['status' => 'completed']);
    $asset->syncStatusWithWorkOrders();
    expect($asset->refresh()->status)->toBe('active');
});
