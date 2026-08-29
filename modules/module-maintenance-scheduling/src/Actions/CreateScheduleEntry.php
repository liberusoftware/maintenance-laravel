<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Scheduling\Models\ScheduleEntry;

class CreateScheduleEntry
{
    public function handle(int $teamId, array $attributes): ScheduleEntry
    {
        $title = trim((string) ($attributes['title'] ?? ''));
        $start = $attributes['starts_at'] ?? null;
        $end = $attributes['ends_at'] ?? null;
        if ($title === '' || $start === null || $end === null) {
            throw ValidationException::withMessages(['title' => 'Title, start, and end are required.']);
        }
        if (strtotime((string) $end) <= strtotime((string) $start)) {
            throw ValidationException::withMessages(['ends_at' => 'The end must be after the start.']);
        }
        $query = ScheduleEntry::where('team_id', $teamId)->where('resource_key', $attributes['resource_key'] ?? null)->where('starts_at', '<', $end)->where('ends_at', '>', $start);
        if ($query->exists()) {
            throw ValidationException::withMessages(['starts_at' => 'The schedule conflicts with an existing entry.']);
        }

        return DB::transaction(fn () => ScheduleEntry::create(array_merge($attributes, ['team_id' => $teamId, 'title' => $title, 'status' => $attributes['status'] ?? 'scheduled'])));
    }
}
