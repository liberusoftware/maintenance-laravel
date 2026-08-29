<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Policies;

use Liberu\Modules\Maintenance\Inspections\Models\Inspection;

class InspectionPolicy
{
    public function viewAny(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function view(object $user, Inspection $inspection): bool
    {
        return (int) $user->currentTeam?->id === (int) $inspection->team_id;
    }

    public function create(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function update(object $user, Inspection $inspection): bool
    {
        return $this->view($user, $inspection);
    }

    public function delete(object $user, Inspection $inspection): bool
    {
        return $this->view($user, $inspection);
    }
}
