<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Policies;

use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrder;

class WorkOrderPolicy
{
    public function viewAny(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function view(object $user, WorkOrder $order): bool
    {
        return (int) $user->currentTeam?->id === (int) $order->team_id;
    }

    public function create(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function update(object $user, WorkOrder $order): bool
    {
        return $this->view($user, $order);
    }

    public function delete(object $user, WorkOrder $order): bool
    {
        return $this->view($user, $order);
    }
}
