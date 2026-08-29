<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Modules\Maintenance\Assets\Models\Asset;

final class DeleteAsset
{
    public function handle(int $teamId, Asset $asset): void
    {
        abort_unless((int) $asset->team_id === $teamId, 404);

        DB::transaction(static fn (): bool => (bool) $asset->delete());
    }
}
