<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrder;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrderTask;

final class CreateWorkOrderTask
{
    public function handle(int $teamId, WorkOrder $workOrder, array $attributes): WorkOrderTask
    {
        abort_unless((int) $workOrder->team_id === $teamId, 404);
        $title = trim((string) ($attributes['title'] ?? ''));
        if ($title === '') {
            throw ValidationException::withMessages(['title' => 'A task title is required.']);
        }

        return DB::transaction(fn (): WorkOrderTask => WorkOrderTask::create(array_merge($attributes, ['team_id' => $teamId, 'work_order_id' => $workOrder->getKey(), 'title' => $title, 'status' => $attributes['status'] ?? 'pending'])));
    }
}
