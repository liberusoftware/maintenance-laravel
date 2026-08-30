<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Notes\Actions;

use Liberu\Modules\Maintenance\Notes\Models\MaintenanceNote;

final class CreateMaintenanceNote
{
    public function handle(int $teamId, array $attributes): MaintenanceNote
    {
        $content = trim((string) ($attributes['content'] ?? ''));
        if ($content === '') abort(422, 'Note content is required.');
        return MaintenanceNote::query()->create(array_merge($attributes, ['team_id' => $teamId, 'content' => $content]))->refresh();
    }
}
