<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Tasks\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Tasks\Models\MaintenanceTask;

final class CompleteMaintenanceTask
{
    public function handle(int $teamId, MaintenanceTask $task): MaintenanceTask
    {
        abort_unless((int) $task->team_id === $teamId, 404);
        if ($task->status === 'completed') throw ValidationException::withMessages(['status' => 'The task is already completed.']);
        $task->update(['status' => 'completed', 'completed_at' => now()]);
        return $task->refresh();
    }
}
