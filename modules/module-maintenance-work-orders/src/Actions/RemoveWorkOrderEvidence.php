<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Actions;

use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrderEvidence;

final class RemoveWorkOrderEvidence
{
    public function handle(int $teamId, WorkOrderEvidence $evidence): void
    {
        abort_unless((int) $evidence->team_id === $teamId, 404);
        $evidence->delete();
    }
}
