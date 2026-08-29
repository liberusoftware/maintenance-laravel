<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\LaborAndTime\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Modules\Maintenance\LaborAndTime\Models\TimeEntry;

final class DeleteTimeEntry
{
    public function handle(int $teamId, TimeEntry $entry): void
    {
        abort_unless((int) $entry->team_id === $teamId, 404);
        DB::transaction(static fn (): bool => (bool) $entry->delete());
    }
}
