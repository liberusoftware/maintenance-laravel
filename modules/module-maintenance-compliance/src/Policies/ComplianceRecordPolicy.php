<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Compliance\Policies;

use Liberu\Modules\Maintenance\Compliance\Models\ComplianceRecord;

class ComplianceRecordPolicy
{
    public function viewAny(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function view(object $user, ComplianceRecord $record): bool
    {
        return (int) $user->currentTeam?->id === (int) $record->team_id;
    }

    public function create(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function update(object $user, ComplianceRecord $record): bool
    {
        return $this->view($user, $record);
    }

    public function delete(object $user, ComplianceRecord $record): bool
    {
        return $this->view($user, $record);
    }
}
