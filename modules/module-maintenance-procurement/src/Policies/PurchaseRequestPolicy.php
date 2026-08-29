<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Policies;

use Liberu\Modules\Maintenance\Procurement\Models\PurchaseRequest;

class PurchaseRequestPolicy
{
    public function viewAny(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function view(object $user, PurchaseRequest $request): bool
    {
        return (int) $user->currentTeam?->id === (int) $request->team_id;
    }

    public function create(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function update(object $user, PurchaseRequest $request): bool
    {
        return $this->view($user, $request);
    }

    public function delete(object $user, PurchaseRequest $request): bool
    {
        return $this->view($user, $request);
    }
}
