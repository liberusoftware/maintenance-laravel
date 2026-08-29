<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Assets\Models\Asset;

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

        return DB::transaction(function () use ($asset, $attributes, $code): Asset {
            $asset->fill(array_merge($attributes, ['code' => $code]));
            $asset->save();

            return $asset->refresh();
        });
    }
}
