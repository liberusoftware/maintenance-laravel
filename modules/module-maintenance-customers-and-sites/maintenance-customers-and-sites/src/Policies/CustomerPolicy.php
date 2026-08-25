<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Policies;

use Liberu\Modules\Maintenance\CustomersAndSites\Models\Customer;

class CustomerPolicy
{
    public function viewAny(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function view(object $user, Customer $customer): bool
    {
        return (int) $user->currentTeam?->id === (int) $customer->team_id;
    }

    public function create(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function update(object $user, Customer $customer): bool
    {
        return $this->view($user, $customer);
    }

    public function delete(object $user, Customer $customer): bool
    {
        return $this->view($user, $customer);
    }
}
