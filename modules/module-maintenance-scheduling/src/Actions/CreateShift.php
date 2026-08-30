<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Scheduling\Models\Shift;

final class CreateShift
{
    public function handle(int $teamId, array $attributes): Shift
    {
        $weekday = (int) ($attributes['weekday'] ?? -1);
        $start = (string) ($attributes['starts_at'] ?? '');
        $end = (string) ($attributes['ends_at'] ?? '');
        if ((int) ($attributes['user_id'] ?? 0) < 1 || $weekday < 0 || $weekday > 6 || $start === '' || $end === '') {
            throw ValidationException::withMessages(['shift' => 'An engineer, weekday, start time, and end time are required.']);
        }
        if (strtotime($end) <= strtotime($start)) {
            throw ValidationException::withMessages(['ends_at' => 'The shift end must be after its start.']);
        }
        if (Shift::query()->where('team_id', $teamId)->where('user_id', $attributes['user_id'])->where('weekday', $weekday)->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists()) {
            throw ValidationException::withMessages(['starts_at' => 'The shift overlaps an existing shift.']);
        }

        return DB::transaction(fn (): Shift => Shift::create(array_merge($attributes, ['team_id' => $teamId, 'weekday' => $weekday, 'timezone' => $attributes['timezone'] ?? 'UTC', 'is_active' => $attributes['is_active'] ?? true])));
    }
}
