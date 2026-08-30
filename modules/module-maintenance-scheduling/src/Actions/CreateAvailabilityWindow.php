<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Scheduling\Models\AvailabilityWindow;

final class CreateAvailabilityWindow
{
    public function handle(int $teamId, array $attributes): AvailabilityWindow
    {
        $weekday = (int) ($attributes['weekday'] ?? -1);
        $startsAt = (string) ($attributes['starts_at'] ?? '');
        $endsAt = (string) ($attributes['ends_at'] ?? '');
        if (empty($attributes['user_id']) || $weekday < 0 || $weekday > 6 || $startsAt === '' || $endsAt === '') {
            throw ValidationException::withMessages(['availability' => 'A user, weekday, start time, and end time are required.']);
        }
        if (strtotime($endsAt) <= strtotime($startsAt)) {
            throw ValidationException::withMessages(['ends_at' => 'The end time must be after the start time.']);
        }
        if (AvailabilityWindow::query()->where('team_id', $teamId)->where('user_id', $attributes['user_id'])->where('weekday', $weekday)->where('starts_at', '<', $endsAt)->where('ends_at', '>', $startsAt)->exists()) {
            throw ValidationException::withMessages(['starts_at' => 'The availability window overlaps an existing window.']);
        }

        return DB::transaction(fn (): AvailabilityWindow => AvailabilityWindow::create(array_merge($attributes, ['team_id' => $teamId, 'weekday' => $weekday, 'timezone' => $attributes['timezone'] ?? 'UTC', 'is_available' => $attributes['is_available'] ?? true])));
    }
}
