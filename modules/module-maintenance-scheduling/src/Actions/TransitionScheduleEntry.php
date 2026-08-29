<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Scheduling\Models\ScheduleEntry;

final class TransitionScheduleEntry
{
    /** @var array<string, list<string>> */
    private const ALLOWED = [
        'scheduled' => ['in_progress', 'cancelled'],
        'in_progress' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    public function handle(int $teamId, ScheduleEntry $entry, string $status): ScheduleEntry
    {
        abort_unless((int) $entry->team_id === $teamId, 404);
        if (! in_array($status, self::ALLOWED[$entry->status] ?? [], true)) {
            throw ValidationException::withMessages(['status' => 'That schedule transition is not allowed.']);
        }

        $entry->update(['status' => $status]);

        return $entry->refresh();
    }
}
