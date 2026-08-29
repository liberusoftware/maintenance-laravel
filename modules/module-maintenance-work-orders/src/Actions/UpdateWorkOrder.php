<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrder;

final class UpdateWorkOrder
{
    /** @param array<string, mixed> $attributes */
    public function handle(int $teamId, WorkOrder $workOrder, array $attributes): WorkOrder
    {
        abort_unless((int) $workOrder->team_id === $teamId, 404);

        if (array_key_exists('status', $attributes) && $attributes['status'] !== $workOrder->status) {
            throw ValidationException::withMessages(['status' => 'Use the transition action to change work-order status.']);
        }

        if (array_key_exists('title', $attributes) && trim((string) $attributes['title']) === '') {
            throw ValidationException::withMessages(['title' => 'A title is required.']);
        }

        return DB::transaction(function () use ($workOrder, $attributes): WorkOrder {
            $workOrder->fill($attributes);
            $workOrder->save();

            return $workOrder->refresh();
        });
    }
}
