<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Policies;

use Liberu\Modules\Maintenance\Scheduling\Models\Territory;

final class TerritoryPolicy
{
    public function viewAny(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function view(object $user, Territory $territory): bool
    {
        return (int) $user->currentTeam?->id === (int) $territory->team_id;
    }

    public function create(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function update(object $user, Territory $territory): bool
    {
        return $this->view($user, $territory);
    }

    public function delete(object $user, Territory $territory): bool
    {
        return $this->view($user, $territory);
    }
}
