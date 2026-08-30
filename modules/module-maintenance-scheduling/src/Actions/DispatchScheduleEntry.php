<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Scheduling\Models\Dispatch;
use Liberu\Modules\Maintenance\Scheduling\Models\ScheduleEntry;

final class DispatchScheduleEntry
{
    public function handle(int $teamId, ScheduleEntry $entry, int $userId, ?int $actorId = null, ?string $notes = null): Dispatch
    {
        abort_unless((int) $entry->team_id === $teamId, 404);
        if ($userId < 1) throw ValidationException::withMessages(['user_id' => 'An engineer is required.']);
        if (Dispatch::query()->where('schedule_entry_id', $entry->getKey())->where('user_id', $userId)->whereIn('status', ['offered', 'accepted'])->exists()) throw ValidationException::withMessages(['user_id' => 'This engineer has already been dispatched to the schedule entry.']);

        return DB::transaction(fn (): Dispatch => Dispatch::create(['team_id' => $teamId, 'schedule_entry_id' => $entry->getKey(), 'user_id' => $userId, 'dispatched_by' => $actorId, 'status' => 'offered', 'dispatched_at' => now(), 'notes' => $notes]));
    }
}
