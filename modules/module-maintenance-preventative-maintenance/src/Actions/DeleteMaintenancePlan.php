<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\PreventativeMaintenance\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Models\MaintenancePlan;

final class DeleteMaintenancePlan
{
    public function handle(int $teamId, MaintenancePlan $plan): void
    {
        abort_unless((int) $plan->team_id === $teamId, 404);
        DB::transaction(static fn (): bool => (bool) $plan->delete());
    }
}
