<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Policies;

class AssetPolicy
{
    public function viewAny(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function view(object $user, object $asset): bool
    {
        return (int) $user->currentTeam?->id === (int) $asset->team_id;
    }

    public function create(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function update(object $user, object $asset): bool
    {
        return $this->view($user, $asset);
    }

    public function delete(object $user, object $asset): bool
    {
        return $this->view($user, $asset);
    }
}
