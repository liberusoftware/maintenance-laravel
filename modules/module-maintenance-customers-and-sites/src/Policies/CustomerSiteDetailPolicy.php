<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Policies;

final class CustomerSiteDetailPolicy
{
    public function viewAny(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function view(object $user, object $record): bool
    {
        return (int) $user->currentTeam?->id === (int) $record->team_id;
    }

    public function create(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function update(object $user, object $record): bool
    {
        return $this->view($user, $record);
    }

    public function delete(object $user, object $record): bool
    {
        return $this->view($user, $record);
    }
}
