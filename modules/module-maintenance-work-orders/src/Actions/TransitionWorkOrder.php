<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrder;

class TransitionWorkOrder
{
    public function handle(int $teamId, WorkOrder $workOrder, string $status, ?int $actorId = null): WorkOrder
    {
        if ((int) $workOrder->team_id !== $teamId) {
            abort(404);
        }

        $allowed = ['requested' => ['triaged', 'cancelled'], 'triaged' => ['in_progress', 'cancelled'], 'in_progress' => ['completed', 'blocked'], 'blocked' => ['in_progress', 'cancelled'], 'completed' => [], 'cancelled' => []];
        if (! in_array($status, $allowed[$workOrder->status] ?? [], true)) {
            throw ValidationException::withMessages(['status' => 'That work-order transition is not allowed.']);
        }
        if ($status === 'completed' && $workOrder->dependencies()->whereHas('dependsOn', fn ($query) => $query->where('status', '!=', 'completed'))->exists()) {
            throw ValidationException::withMessages(['status' => 'All prerequisite work orders must be completed first.']);
        }

        return DB::transaction(function () use ($workOrder, $status, $actorId): WorkOrder {
            $metadata = is_array($workOrder->metadata) ? $workOrder->metadata : [];
            $history = is_array($metadata['status_history'] ?? null) ? $metadata['status_history'] : [];
            $history[] = ['from' => $workOrder->status, 'to' => $status, 'actor_id' => $actorId, 'at' => now()->toISOString()];
            $metadata['status_history'] = $history;

            $workOrder->status = $status;
            $workOrder->metadata = $metadata;
            if ($status === 'in_progress' && $workOrder->started_at === null) {
                $workOrder->started_at = now();
            }
            if ($status === 'completed') {
                $workOrder->completed_at = now();
            }
            $workOrder->save();

            return $workOrder->refresh();
        });
    }
}
