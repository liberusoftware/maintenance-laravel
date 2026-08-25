<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Portal\Policies;

use Liberu\Modules\Maintenance\Portal\Models\PortalRecord;

class PortalRecordPolicy
{
    public function viewAny(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function view(object $user, PortalRecord $record): bool
    {
        return (int) $user->currentTeam?->id === (int) $record->team_id;
    }

    public function create(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function update(object $user, PortalRecord $record): bool
    {
        return $this->view($user, $record);
    }

    public function delete(object $user, PortalRecord $record): bool
    {
        return $this->view($user, $record);
    }
}
