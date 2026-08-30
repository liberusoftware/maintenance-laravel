<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Assets\Models\Asset;
use Liberu\Modules\Maintenance\Assets\Models\AssetCategory;

final class UpdateAsset
{
    /** @param array<string, mixed> $attributes */
    public function handle(int $teamId, Asset $asset, array $attributes): Asset
    {
        abort_unless((int) $asset->team_id === $teamId, 404);

        $code = array_key_exists('code', $attributes)
            ? strtoupper(trim((string) $attributes['code']))
            : $asset->code;

        if ($code === '') {
            throw ValidationException::withMessages(['code' => 'The asset code is required.']);
        }

        $duplicate = Asset::query()
            ->where('team_id', $teamId)
            ->where('code', $code)
            ->whereKeyNot($asset->getKey())
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages(['code' => 'The asset code is already in use.']);
        }
        if (array_key_exists('parent_id', $attributes) && $attributes['parent_id'] !== null && (! Asset::query()->where('team_id', $teamId)->whereKey($attributes['parent_id'])->exists() || (int) $attributes['parent_id'] === (int) $asset->getKey())) {
            throw ValidationException::withMessages(['parent_id' => 'The parent asset is invalid.']);
        }
        if (array_key_exists('category_id', $attributes) && $attributes['category_id'] !== null && ! AssetCategory::query()->where('team_id', $teamId)->whereKey($attributes['category_id'])->exists()) {
            throw ValidationException::withMessages(['category_id' => 'The asset category is invalid.']);
        }

        return DB::transaction(function () use ($asset, $attributes, $code): Asset {
            $asset->fill(array_merge($attributes, ['code' => $code]));
            $asset->save();

            return $asset->refresh();
        });
    }
}
