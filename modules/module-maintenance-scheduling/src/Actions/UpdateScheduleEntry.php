<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Scheduling\Models\ScheduleEntry;

final class UpdateScheduleEntry
{
    /** @param array<string, mixed> $attributes */
    public function handle(int $teamId, ScheduleEntry $entry, array $attributes): ScheduleEntry
    {
        abort_unless((int) $entry->team_id === $teamId, 404);
        $start = $attributes['starts_at'] ?? $entry->starts_at;
        $end = $attributes['ends_at'] ?? $entry->ends_at;
        if (strtotime((string) $end) <= strtotime((string) $start)) {
            throw ValidationException::withMessages(['ends_at' => 'The end must be after the start.']);
        }
        $resourceKey = array_key_exists('resource_key', $attributes) ? $attributes['resource_key'] : $entry->resource_key;
        $status = array_key_exists('status', $attributes) ? (string) $attributes['status'] : $entry->status;
        if (! in_array($status, ['scheduled', 'in_progress', 'completed', 'cancelled'], true)) {
            throw ValidationException::withMessages(['status' => 'The schedule status is invalid.']);
        }
        $conflict = ScheduleEntry::query()
            ->where('team_id', $teamId)
            ->where('resource_key', $resourceKey)
            ->whereKeyNot($entry->getKey())
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->exists();
        if ($conflict) {
            throw ValidationException::withMessages(['starts_at' => 'The schedule conflicts with an existing entry.']);
        }

        return DB::transaction(function () use ($entry, $attributes, $start, $end): ScheduleEntry {
            $entry->fill(array_merge($attributes, ['starts_at' => $start, 'ends_at' => $end]));
            $entry->save();

            return $entry->refresh();
        });
    }
}
