<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrder;

class TransitionWorkOrder
{
    public function handle(int $teamId, WorkOrder $workOrder, string $status): WorkOrder
    {
        if ((int) $workOrder->team_id !== $teamId) {
            abort(404);
        } $allowed = ['requested' => ['triaged', 'cancelled'], 'triaged' => ['in_progress', 'cancelled'], 'in_progress' => ['completed', 'blocked'], 'blocked' => ['in_progress', 'cancelled'], 'completed' => [], 'cancelled' => []];
        if (! in_array($status, $allowed[$workOrder->status] ?? [], true)) {
            throw ValidationException::withMessages(['status' => 'That work-order transition is not allowed.']);
        }
        $workOrder->status = $status;
        if ($status === 'in_progress' && $workOrder->started_at === null) {
            $workOrder->started_at = now();
        }
        if ($status === 'completed') {
            $workOrder->completed_at = now();
        } $workOrder->save();

        return $workOrder->refresh();
    }
}
