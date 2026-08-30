<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrderTask;

final class CompleteWorkOrderTask
{
    public function handle(int $teamId, WorkOrderTask $task): WorkOrderTask
    {
        abort_unless((int) $task->team_id === $teamId, 404);
        if ($task->status === 'cancelled') {
            throw ValidationException::withMessages(['status' => 'A cancelled task cannot be completed.']);
        }

        return DB::transaction(function () use ($task): WorkOrderTask {
            $task->forceFill(['status' => 'completed', 'completed_at' => $task->completed_at ?? now()])->save();

            return $task->refresh();
        });
    }
}
