<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Scheduling\Models\ScheduleEntry;
use Liberu\Modules\Maintenance\Scheduling\Models\TravelSegment;

final class CreateTravelSegment
{
    public function handle(int $teamId, ScheduleEntry $entry, array $attributes): TravelSegment
    {
        abort_unless((int) $entry->team_id === $teamId, 404);
        $origin = trim((string) ($attributes['origin'] ?? ''));
        $destination = trim((string) ($attributes['destination'] ?? ''));
        if ($origin === '' || $destination === '') {
            throw ValidationException::withMessages(['travel' => 'Origin and destination are required.']);
        }
        if (isset($attributes['planned_minutes']) && (int) $attributes['planned_minutes'] < 0) {
            throw ValidationException::withMessages(['planned_minutes' => 'Travel duration cannot be negative.']);
        }

        return DB::transaction(fn (): TravelSegment => TravelSegment::create(array_merge($attributes, ['team_id' => $teamId, 'schedule_entry_id' => $entry->getKey(), 'origin' => $origin, 'destination' => $destination])));
    }
}
