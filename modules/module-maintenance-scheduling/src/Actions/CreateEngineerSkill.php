<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Scheduling\Models\EngineerSkill;

final class CreateEngineerSkill
{
    public function handle(int $teamId, array $attributes): EngineerSkill
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $userId = (int) ($attributes['user_id'] ?? 0);
        $proficiency = (int) ($attributes['proficiency'] ?? 1);
        if ($userId < 1 || $name === '' || $proficiency < 1 || $proficiency > 5) throw ValidationException::withMessages(['skill' => 'An engineer, skill name, and proficiency from one to five are required.']);
        if (EngineerSkill::query()->where('team_id', $teamId)->where('user_id', $userId)->where('name', $name)->exists()) throw ValidationException::withMessages(['name' => 'That engineer skill already exists.']);

        return DB::transaction(fn (): EngineerSkill => EngineerSkill::create(array_merge($attributes, ['team_id' => $teamId, 'user_id' => $userId, 'name' => $name, 'proficiency' => $proficiency])));
    }
}
