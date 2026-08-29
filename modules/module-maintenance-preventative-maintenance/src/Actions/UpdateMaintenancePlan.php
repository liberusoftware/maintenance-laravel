<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\PreventativeMaintenance\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Models\MaintenancePlan;

final class UpdateMaintenancePlan
{
    /** @param array<string, mixed> $attributes */
    public function handle(int $teamId, MaintenancePlan $plan, array $attributes): MaintenancePlan
    {
        abort_unless((int) $plan->team_id === $teamId, 404);
        $name = array_key_exists('name', $attributes) ? trim((string) $attributes['name']) : $plan->name;
        $code = array_key_exists('code', $attributes) ? strtoupper(trim((string) $attributes['code'])) : $plan->code;
        $frequency = array_key_exists('frequency_value', $attributes) ? (int) $attributes['frequency_value'] : (int) $plan->frequency_value;
        $unit = array_key_exists('frequency_unit', $attributes) ? (string) $attributes['frequency_unit'] : $plan->frequency_unit;
        if ($name === '' || $code === '' || $frequency < 1 || ! in_array($unit, ['hours', 'days', 'weeks', 'months', 'years', 'meters'], true)) {
            throw ValidationException::withMessages(['name' => 'Name, code, and a positive frequency are required.']);
        }
        if (MaintenancePlan::query()->where('team_id', $teamId)->where('code', $code)->whereKeyNot($plan->getKey())->exists()) {
            throw ValidationException::withMessages(['code' => 'The plan code is already in use.']);
        }

        return DB::transaction(function () use ($plan, $attributes, $name, $code, $frequency): MaintenancePlan {
            $plan->fill(array_merge($attributes, ['name' => $name, 'code' => $code, 'frequency_value' => $frequency, 'frequency_unit' => $unit]));
            $plan->save();

            return $plan->refresh();
        });
    }
}
