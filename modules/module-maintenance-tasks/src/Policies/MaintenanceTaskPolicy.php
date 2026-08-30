<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Tasks\Policies;

use Liberu\Modules\Maintenance\Tasks\Models\MaintenanceTask;

final class MaintenanceTaskPolicy
{
    public function viewAny(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function view(object $user, MaintenanceTask $task): bool
    {
        return (int) $user->currentTeam?->id === (int) $task->team_id;
    }

    public function create(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function update(object $user, MaintenanceTask $task): bool
    {
        return $this->view($user, $task);
    }

    public function delete(object $user, MaintenanceTask $task): bool
    {
        return $this->view($user, $task);
    }
}
