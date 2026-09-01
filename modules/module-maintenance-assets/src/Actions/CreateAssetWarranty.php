<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Assets\Models\Asset;
use Liberu\Modules\Maintenance\Assets\Models\AssetWarranty;

final class CreateAssetWarranty
{
    public function handle(int $teamId, Asset $asset, array $attributes): AssetWarranty
    {
        abort_unless((int) $asset->team_id === $teamId, 404);
        if (empty($attributes['expires_on'])) {
            throw ValidationException::withMessages(['expires_on' => 'An expiry date is required.']);
        }
        if (! empty($attributes['starts_on']) && $attributes['expires_on'] < $attributes['starts_on']) {
            throw ValidationException::withMessages(['expires_on' => 'The expiry date must not precede the start date.']);
        }

        return DB::transaction(fn (): AssetWarranty => AssetWarranty::create(array_merge($attributes, ['team_id' => $teamId, 'asset_id' => $asset->getKey(), 'status' => $attributes['status'] ?? 'active'])));
    }
}
