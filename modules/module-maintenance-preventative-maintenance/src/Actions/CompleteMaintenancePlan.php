<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\PreventativeMaintenance\Actions;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Models\MaintenancePlan;

final class CompleteMaintenancePlan
{
    public function handle(int $teamId, MaintenancePlan $plan, ?CarbonInterface $completedAt = null): MaintenancePlan
    {
        abort_unless((int) $plan->team_id === $teamId, 404);

        return DB::transaction(function () use ($plan, $completedAt): MaintenancePlan {
            $plan->last_completed_at = $completedAt ?? now();
            $plan->next_due_at = $plan->nextDueAfterCompletion();
            $plan->save();

            return $plan->refresh();
        });
    }
}
