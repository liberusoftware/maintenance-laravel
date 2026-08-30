<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Assets\Models\Asset;
use Liberu\Modules\Maintenance\Assets\Models\AssetSpecification;

final class CreateAssetSpecification
{
    public function handle(int $teamId, Asset $asset, array $attributes): AssetSpecification
    {
        abort_unless((int) $asset->team_id === $teamId, 404);
        $key = trim((string) ($attributes['key'] ?? ''));
        if ($key === '' || trim((string) ($attributes['value'] ?? '')) === '') throw ValidationException::withMessages(['key' => 'A specification key and value are required.']);
        if (AssetSpecification::query()->where('asset_id', $asset->getKey())->where('key', $key)->exists()) throw ValidationException::withMessages(['key' => 'That specification already exists for the asset.']);

        return DB::transaction(fn (): AssetSpecification => AssetSpecification::create(array_merge($attributes, ['team_id' => $teamId, 'asset_id' => $asset->getKey(), 'key' => $key])));
    }
}
