<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Modules\Maintenance\Scheduling\Models\ScheduleEntry;

final class DeleteScheduleEntry
{
    public function handle(int $teamId, ScheduleEntry $entry): void
    {
        abort_unless((int) $entry->team_id === $teamId, 404);
        DB::transaction(static fn (): bool => (bool) $entry->delete());
    }
}
