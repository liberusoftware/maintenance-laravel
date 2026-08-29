<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\LaborAndTime\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\LaborAndTime\Models\TimeEntry;

class CreateTimeEntry
{
    public function handle(int $teamId, array $attributes): TimeEntry
    {
        $minutes = (int) ($attributes['minutes'] ?? 0);
        if ($minutes < 1) {
            throw ValidationException::withMessages(['minutes' => 'Time must contain at least one minute.']);
        }

        return DB::transaction(fn () => TimeEntry::create(array_merge($attributes, ['team_id' => $teamId, 'user_id' => $attributes['user_id'] ?? null, 'minutes' => $minutes, 'status' => 'pending', 'currency' => $attributes['currency'] ?? 'USD'])));
    }
}
