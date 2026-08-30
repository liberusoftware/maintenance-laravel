<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\LaborAndTime\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\LaborAndTime\Models\Attendance;

final class CreateAttendance
{
    public function handle(int $teamId, array $attributes): Attendance
    {
        if (empty($attributes['user_id']) || empty($attributes['attendance_date'])) {
            throw ValidationException::withMessages(['attendance_date' => 'A user and attendance date are required.']);
        }
        if (! empty($attributes['clocked_in_at']) && ! empty($attributes['clocked_out_at']) && $attributes['clocked_out_at'] <= $attributes['clocked_in_at']) {
            throw ValidationException::withMessages(['clocked_out_at' => 'Clock-out must be after clock-in.']);
        }

        return DB::transaction(fn (): Attendance => Attendance::create(array_merge($attributes, ['team_id' => $teamId, 'status' => $attributes['status'] ?? 'present'])));
    }
}
