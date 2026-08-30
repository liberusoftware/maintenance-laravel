<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Assets\Models\Asset;
use Liberu\Modules\Maintenance\Assets\Models\AssetMeter;
use Liberu\Modules\Maintenance\Assets\Models\AssetMeterReading;

final class RecordAssetMeterReading
{
    public function handle(int $teamId, Asset $asset, AssetMeter $meter, float|int $value, ?string $recordedAt = null, ?int $actorId = null, ?string $notes = null): AssetMeterReading
    {
        abort_unless((int) $asset->team_id === $teamId && (int) $meter->team_id === $teamId && (int) $meter->asset_id === (int) $asset->getKey(), 404);
        if (! is_finite((float) $value)) {
            throw ValidationException::withMessages(['value' => 'The meter reading must be finite.']);
        }
        $recordedAt ??= now()->toISOString();

        return DB::transaction(function () use ($teamId, $asset, $meter, $value, $recordedAt, $actorId, $notes): AssetMeterReading {
            $reading = AssetMeterReading::query()->create(['team_id' => $teamId, 'asset_id' => $asset->getKey(), 'meter_id' => $meter->getKey(), 'value' => $value, 'recorded_at' => $recordedAt, 'recorded_by' => $actorId, 'notes' => $notes]);
            $meter->forceFill(['current_value' => $value, 'last_reading_at' => $recordedAt])->save();

            return $reading->refresh();
        });
    }
}
