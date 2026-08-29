<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Policies;

use Liberu\Modules\Maintenance\Scheduling\Models\ScheduleEntry;

class ScheduleEntryPolicy
{
    public function viewAny(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function view(object $user, ScheduleEntry $entry): bool
    {
        return (int) $user->currentTeam?->id === (int) $entry->team_id;
    }

    public function create(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function update(object $user, ScheduleEntry $entry): bool
    {
        return $this->view($user, $entry);
    }

    public function delete(object $user, ScheduleEntry $entry): bool
    {
        return $this->view($user, $entry);
    }
}
