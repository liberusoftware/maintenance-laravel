<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Liberu\Modules\Maintenance\Core\Models\Organization;

final class OrganizationPolicy
{
    public function viewAny(AuthUser $user): bool
    {
        return $this->currentTeamId($user) !== null;
    }

    public function view(AuthUser $user, Organization $organization): bool
    {
        return $this->canAccess($user, $organization);
    }

    public function create(AuthUser $user): bool
    {
        return $this->currentTeamId($user) !== null;
    }

    public function update(AuthUser $user, Organization $organization): bool
    {
        return $this->canAccess($user, $organization);
    }

    public function delete(AuthUser $user, Organization $organization): bool
    {
        return $this->canAccess($user, $organization);
    }

    private function canAccess(AuthUser $user, Organization $organization): bool
    {
        return $this->currentTeamId($user) === (int) $organization->team_id;
    }

    private function currentTeamId(AuthUser $user): ?int
    {
        $team = $user->currentTeam ?? null;

        return $team?->getKey() === null ? null : (int) $team->getKey();
    }
}
