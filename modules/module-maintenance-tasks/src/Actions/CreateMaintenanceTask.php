<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Tasks\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Tasks\Models\MaintenanceTask;

final class CreateMaintenanceTask
{
    public function handle(int $teamId, array $attributes): MaintenanceTask
    {
        $description = trim((string) ($attributes['description'] ?? ''));
        if ($description === '') throw ValidationException::withMessages(['description' => 'Task description is required.']);
        return MaintenanceTask::query()->create(array_merge(['team_id' => $teamId, 'description' => $description, 'status' => 'pending'], $attributes))->refresh();
    }
}
