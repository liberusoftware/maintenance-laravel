<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrder;

final class DeleteWorkOrder
{
    public function handle(int $teamId, WorkOrder $workOrder): void
    {
        abort_unless((int) $workOrder->team_id === $teamId, 404);
        DB::transaction(static fn (): bool => (bool) $workOrder->delete());
    }
}
