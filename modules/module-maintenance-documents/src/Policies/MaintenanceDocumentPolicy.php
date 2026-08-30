<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Documents\Policies;

use Liberu\Modules\Maintenance\Documents\Models\MaintenanceDocument;

final class MaintenanceDocumentPolicy
{
    public function viewAny(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function view(object $user, MaintenanceDocument $document): bool
    {
        return (int) $user->currentTeam?->id === (int) $document->team_id;
    }

    public function create(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function update(object $user, MaintenanceDocument $document): bool
    {
        return $this->view($user, $document);
    }

    public function delete(object $user, MaintenanceDocument $document): bool
    {
        return $this->view($user, $document);
    }
}
