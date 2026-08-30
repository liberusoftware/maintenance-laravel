<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Scheduling\Models\Territory;

final class CreateTerritory
{
    /** @param array<string, mixed> $attributes */
    public function handle(int $teamId, array $attributes): Territory
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $code = trim((string) ($attributes['code'] ?? ''));
        if ($name === '' || $code === '') {
            throw ValidationException::withMessages(['territory' => 'A territory name and code are required.']);
        }
        if (Territory::query()->where('team_id', $teamId)->where('code', $code)->exists()) {
            throw ValidationException::withMessages(['code' => 'That territory code already exists.']);
        }

        return DB::transaction(fn (): Territory => Territory::create(array_merge($attributes, ['team_id' => $teamId, 'name' => $name, 'code' => $code, 'is_active' => $attributes['is_active'] ?? true]))->refresh());
    }
}
