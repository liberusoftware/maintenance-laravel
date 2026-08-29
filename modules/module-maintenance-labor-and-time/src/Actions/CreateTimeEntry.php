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
        $this->validateAmounts($attributes);
        $this->validateTimeRange($attributes);

        return DB::transaction(fn () => TimeEntry::create(array_merge($attributes, ['team_id' => $teamId, 'user_id' => $attributes['user_id'] ?? null, 'minutes' => $minutes, 'status' => 'pending', 'currency' => $attributes['currency'] ?? 'USD'])));
    }

    private function validateAmounts(array $attributes): void
    {
        if (isset($attributes['rate']) && (float) $attributes['rate'] < 0) {
            throw ValidationException::withMessages(['rate' => 'The rate cannot be negative.']);
        }
        if (isset($attributes['expense_amount']) && (float) $attributes['expense_amount'] < 0) {
            throw ValidationException::withMessages(['expense_amount' => 'The expense amount cannot be negative.']);
        }
    }

    private function validateTimeRange(array $attributes): void
    {
        if (($attributes['started_at'] ?? null) !== null && ($attributes['ended_at'] ?? null) !== null && strtotime((string) $attributes['ended_at']) <= strtotime((string) $attributes['started_at'])) {
            throw ValidationException::withMessages(['ended_at' => 'The end time must be after the start time.']);
        }
    }
}
