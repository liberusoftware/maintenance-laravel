<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Policies;

use Liberu\Modules\Maintenance\Procurement\Models\VendorContract;

class VendorContractPolicy
{
    public function viewAny(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function view(object $user, VendorContract $contract): bool
    {
        return (int) $user->currentTeam?->getKey() === (int) $contract->team_id;
    }

    public function create(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function update(object $user, VendorContract $contract): bool
    {
        return $this->view($user, $contract);
    }

    public function delete(object $user, VendorContract $contract): bool
    {
        return $this->view($user, $contract);
    }
}
