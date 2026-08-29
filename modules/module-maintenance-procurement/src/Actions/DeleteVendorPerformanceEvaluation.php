<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Actions;

use Liberu\Modules\Maintenance\Procurement\Models\VendorPerformanceEvaluation;

final class DeleteVendorPerformanceEvaluation
{
    public function handle(int $teamId, VendorPerformanceEvaluation $evaluation): void
    {
        abort_unless((int) $evaluation->team_id === $teamId, 404);
        $evaluation->delete();
    }
}
