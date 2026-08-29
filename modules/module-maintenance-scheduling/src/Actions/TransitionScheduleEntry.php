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

        $attributes = ['status' => $status];
        if ($status === 'completed') {
            $completedAt = now();
            $attributes['last_completed_at'] = $completedAt;
            $nextDueAt = $entry->calculateNextDueAt($completedAt);
            $attributes['next_due_at'] = $nextDueAt;

            // Recurring entries remain schedulable for their next occurrence;
            // one-off entries become terminal after completion.
            if ($nextDueAt !== null) {
                $attributes['status'] = 'scheduled';
            }
        }
        $entry->update($attributes);

        return $entry->refresh();
    }
}
