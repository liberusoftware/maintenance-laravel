<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\LaborAndTime\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\LaborAndTime\Models\TimeEntry;

final class UpdateTimeEntry
{
    /** @param array<string, mixed> $attributes */
    public function handle(int $teamId, TimeEntry $entry, array $attributes): TimeEntry
    {
        abort_unless((int) $entry->team_id === $teamId, 404);
        if (array_key_exists('status', $attributes) && $attributes['status'] !== $entry->status) {
            throw ValidationException::withMessages(['status' => 'Use the approval action to change time-entry status.']);
        }
        $minutes = array_key_exists('minutes', $attributes) ? (int) $attributes['minutes'] : (int) $entry->minutes;
        if ($minutes < 1) {
            throw ValidationException::withMessages(['minutes' => 'Time must contain at least one minute.']);
        }

        return DB::transaction(function () use ($entry, $attributes, $minutes): TimeEntry {
            $entry->fill(array_merge($attributes, ['minutes' => $minutes]));
            $entry->save();

            return $entry->refresh();
        });
    }
}
