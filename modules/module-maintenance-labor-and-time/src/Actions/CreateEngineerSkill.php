<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\LaborAndTime\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\LaborAndTime\Models\EngineerSkill;

final class CreateEngineerSkill
{
    public function handle(int $teamId, array $attributes): EngineerSkill
    {
        $skill = trim((string) ($attributes['skill'] ?? ''));
        $level = (int) ($attributes['level'] ?? 1);
        if ($skill === '' || $level < 1 || $level > 5 || empty($attributes['user_id'])) {
            throw ValidationException::withMessages(['skill' => 'A user, skill, and level from 1 to 5 are required.']);
        }

        return DB::transaction(fn (): EngineerSkill => EngineerSkill::create(array_merge($attributes, ['team_id' => $teamId, 'skill' => $skill, 'level' => $level])));
    }
}
