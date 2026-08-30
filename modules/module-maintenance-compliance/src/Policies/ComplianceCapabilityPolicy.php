<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Compliance\Policies;

final class ComplianceCapabilityPolicy
{
    public function viewAny(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function create(object $user): bool
    {
        return $user->currentTeam !== null;
    }
}
