<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Assets\Models\Asset;
use Liberu\Modules\Maintenance\Assets\Models\AssetMeter;

final class CreateAssetMeter
{
    public function handle(int $teamId, Asset $asset, array $attributes): AssetMeter
    {
        abort_unless((int) $asset->team_id === $teamId, 404);
        $name = trim((string) ($attributes['name'] ?? ''));
        $unit = trim((string) ($attributes['unit'] ?? ''));
        if ($name === '' || $unit === '') {
            throw ValidationException::withMessages(['name' => 'A meter name and unit are required.']);
        }
        if (AssetMeter::query()->where('asset_id', $asset->getKey())->where('name', $name)->exists()) {
            throw ValidationException::withMessages(['name' => 'The meter name is already in use for this asset.']);
        }

        return DB::transaction(fn (): AssetMeter => AssetMeter::query()->create(array_merge($attributes, ['team_id' => $teamId, 'asset_id' => $asset->getKey(), 'name' => $name, 'unit' => $unit, 'is_active' => (bool) ($attributes['is_active'] ?? true)]))->refresh());
    }
}
