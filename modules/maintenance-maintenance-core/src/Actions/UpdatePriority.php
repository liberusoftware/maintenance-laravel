<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Core\Events\PriorityUpdated;
use Liberu\Modules\Maintenance\Core\Models\Priority;

final class UpdatePriority
{
    /** @param array<string, mixed> $attributes */
    public function execute(Priority $priority, array $attributes): Priority
    {
        $name = trim((string) ($attributes['name'] ?? $priority->name));
        $code = strtoupper(trim((string) ($attributes['code'] ?? $priority->code)));
        if ($name === '' || $code === '') {
            throw ValidationException::withMessages(['name' => 'A name and code are required.']);
        }
        if (Priority::query()->where('team_id', $priority->team_id)->where('code', $code)->whereKeyNot($priority->getKey())->exists()) {
            throw ValidationException::withMessages(['code' => 'The priority code is already in use.']);
        }
        if (($attributes['is_default'] ?? $priority->is_default) === true) {
            Priority::query()->where('team_id', $priority->team_id)->whereKeyNot($priority->getKey())->update(['is_default' => false]);
        }
        $priority->update([
            'name' => $name, 'code' => $code, 'color' => $attributes['color'] ?? $priority->color,
            'sort_order' => (int) ($attributes['sort_order'] ?? $priority->sort_order),
            'is_default' => (bool) ($attributes['is_default'] ?? $priority->is_default),
            'is_active' => (bool) ($attributes['is_active'] ?? $priority->is_active),
        ]);

        $priority = $priority->refresh();
        PriorityUpdated::dispatch($priority->getKey(), $priority->team_id);

        return $priority;
    }
}
