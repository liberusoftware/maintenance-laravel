<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Modules\Maintenance\Scheduling\Models\Territory;

final class DeleteTerritory
{
    public function handle(int $teamId, Territory $territory): void
    {
        abort_unless((int) $territory->team_id === $teamId, 404);
        DB::transaction(static fn (): bool => (bool) $territory->delete());
    }
}
