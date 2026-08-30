<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\LaborAndTime\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\LaborAndTime\Models\LaborRate;

final class CreateLaborRate
{
    public function handle(int $teamId, array $attributes): LaborRate
    {
        if (trim((string) ($attributes['name'] ?? '')) === '' || ! isset($attributes['hourly_rate']) || (float) $attributes['hourly_rate'] < 0 || empty($attributes['effective_from'])) {
            throw ValidationException::withMessages(['name' => 'A name, non-negative hourly rate, and effective date are required.']);
        }

        return DB::transaction(fn (): LaborRate => LaborRate::create(array_merge($attributes, ['team_id' => $teamId, 'name' => trim((string) $attributes['name']), 'currency' => strtoupper((string) ($attributes['currency'] ?? 'USD'))])));
    }
}
