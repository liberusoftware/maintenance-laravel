<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Policies;

use Liberu\Modules\Maintenance\Inspections\Models\InspectionTemplate;

final class InspectionTemplatePolicy
{
    public function viewAny(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function view(object $user, InspectionTemplate $template): bool
    {
        return (int) $user->currentTeam?->id === (int) $template->team_id;
    }

    public function create(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function update(object $user, InspectionTemplate $template): bool
    {
        return $this->view($user, $template);
    }

    public function delete(object $user, InspectionTemplate $template): bool
    {
        return $this->view($user, $template);
    }
}
