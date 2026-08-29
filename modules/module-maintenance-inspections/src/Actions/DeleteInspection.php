<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Modules\Maintenance\Inspections\Models\Inspection;

final class DeleteInspection
{
    public function handle(int $teamId, Inspection $inspection): void
    {
        abort_unless((int) $inspection->team_id === $teamId, 404);
        DB::transaction(static fn (): bool => (bool) $inspection->delete());
    }
}
