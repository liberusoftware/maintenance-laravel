<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Policies;

use Liberu\Modules\Maintenance\Procurement\Models\VendorPerformanceEvaluation;

class VendorPerformanceEvaluationPolicy
{
    public function viewAny(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function view(object $user, VendorPerformanceEvaluation $evaluation): bool
    {
        return (int) $user->currentTeam?->getKey() === (int) $evaluation->team_id;
    }

    public function create(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function update(object $user, VendorPerformanceEvaluation $evaluation): bool
    {
        return $this->view($user, $evaluation);
    }

    public function delete(object $user, VendorPerformanceEvaluation $evaluation): bool
    {
        return $this->view($user, $evaluation);
    }
}
