<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Policies;

use Liberu\Modules\Maintenance\CustomersAndSites\Models\Site;

final class SitePolicy
{
    public function viewAny(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function view(object $user, Site $site): bool
    {
        return (int) $user->currentTeam?->id === (int) $site->team_id;
    }

    public function create(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function update(object $user, Site $site): bool
    {
        return $this->view($user, $site);
    }

    public function delete(object $user, Site $site): bool
    {
        return $this->view($user, $site);
    }
}
