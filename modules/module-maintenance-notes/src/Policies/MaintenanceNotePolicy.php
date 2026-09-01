<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Notes\Policies;

use Liberu\Modules\Maintenance\Notes\Models\MaintenanceNote;

final class MaintenanceNotePolicy
{
    public function viewAny(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function view(object $user, MaintenanceNote $note): bool
    {
        return (int) $user->currentTeam?->id === (int) $note->team_id;
    }

    public function create(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function update(object $user, MaintenanceNote $note): bool
    {
        return $this->view($user, $note);
    }

    public function delete(object $user, MaintenanceNote $note): bool
    {
        return $this->view($user, $note);
    }
}
