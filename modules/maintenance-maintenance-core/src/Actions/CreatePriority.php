<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Core\Models\Priority;

final class CreatePriority
{
    /** @param array<string, mixed> $attributes */
    public function execute(int $teamId, array $attributes): Priority
    {
        $name = trim((string) $attributes['name']);
        $code = strtoupper(trim((string) $attributes['code']));
        if ($name === '' || $code === '') {
            throw ValidationException::withMessages(['name' => 'A name and code are required.']);
        }
        if (Priority::query()->where('team_id', $teamId)->where('code', $code)->exists()) {
            throw ValidationException::withMessages(['code' => 'The priority code is already in use.']);
        }
        if (($attributes['is_default'] ?? false) === true) {
            Priority::query()->where('team_id', $teamId)->update(['is_default' => false]);
        }

        return Priority::query()->create([
            'team_id' => $teamId, 'name' => $name, 'code' => $code,
            'color' => $attributes['color'] ?? null, 'sort_order' => (int) ($attributes['sort_order'] ?? 0),
            'is_default' => (bool) ($attributes['is_default'] ?? false), 'is_active' => (bool) ($attributes['is_active'] ?? true),
        ])->refresh();
    }
}
