<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\LaborAndTime\Policies;

use Liberu\Modules\Maintenance\LaborAndTime\Models\TimeEntry;

class TimeEntryPolicy
{
    public function viewAny(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function view(object $user, TimeEntry $entry): bool
    {
        return (int) $user->currentTeam?->id === (int) $entry->team_id;
    }

    public function create(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function update(object $user, TimeEntry $entry): bool
    {
        return $this->view($user, $entry);
    }

    public function delete(object $user, TimeEntry $entry): bool
    {
        return $this->view($user, $entry);
    }
}
