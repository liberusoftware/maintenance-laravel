<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\PreventativeMaintenance\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Models\MaintenancePlan;

class CreateMaintenancePlan
{
    public function handle(int $teamId, array $attributes): MaintenancePlan
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $code = strtoupper(trim((string) ($attributes['code'] ?? '')));
        $value = (int) ($attributes['frequency_value'] ?? 0);
        if ($name === '' || $code === '' || $value < 1) {
            throw ValidationException::withMessages(['name' => 'Name, code, and a positive frequency are required.']);
        }if (MaintenancePlan::where('team_id', $teamId)->where('code', $code)->exists()) {
            throw ValidationException::withMessages(['code' => 'The plan code is already in use.']);
        }

return DB::transaction(fn () => MaintenancePlan::create(array_merge($attributes, ['team_id' => $teamId, 'name' => $name, 'code' => $code, 'frequency_value' => $value, 'frequency_unit' => $attributes['frequency_unit'] ?? 'days', 'is_active' => $attributes['is_active'] ?? true])));
    }
}
