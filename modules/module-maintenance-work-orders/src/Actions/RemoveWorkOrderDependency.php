<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Actions;

use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrderDependency;

final class RemoveWorkOrderDependency
{
    public function handle(int $teamId, WorkOrderDependency $dependency): void
    {
        abort_unless((int) $dependency->team_id === $teamId, 404);
        $dependency->delete();
    }
}
