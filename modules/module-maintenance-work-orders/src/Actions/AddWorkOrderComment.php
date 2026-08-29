<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrder;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrderComment;

final class AddWorkOrderComment
{
    public function handle(int $teamId, WorkOrder $workOrder, int $userId, string $comment, bool $internal = false): WorkOrderComment
    {
        abort_unless((int) $workOrder->team_id === $teamId, 404);
        $comment = trim($comment);
        if ($comment === '') {
            throw ValidationException::withMessages(['comment' => 'A comment is required.']);
        }

        return WorkOrderComment::query()->create([
            'team_id' => $teamId,
            'work_order_id' => $workOrder->getKey(),
            'user_id' => $userId,
            'comment' => $comment,
            'is_internal' => $internal,
        ])->refresh();
    }
}
