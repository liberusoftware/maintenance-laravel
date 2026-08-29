<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\PreventativeMaintenance\Policies;

use Liberu\Modules\Maintenance\PreventativeMaintenance\Models\MaintenancePlan;

class MaintenancePlanPolicy
{
    public function viewAny(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function view(object $user, MaintenancePlan $plan): bool
    {
        return (int) $user->currentTeam?->id === (int) $plan->team_id;
    }

    public function create(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function update(object $user, MaintenancePlan $plan): bool
    {
        return $this->view($user, $plan);
    }

    public function delete(object $user, MaintenancePlan $plan): bool
    {
        return $this->view($user, $plan);
    }
}
