<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Modules\Maintenance\Core\Events\OrganizationDeleted;
use Liberu\Modules\Maintenance\Core\Models\Organization;

final class DeleteOrganization
{
    public function execute(Organization $organization): void
    {
        DB::transaction(function () use ($organization): void {
            $teamId = $organization->team_id;
            $id = $organization->getKey();
            $organization->delete();
            OrganizationDeleted::dispatch($id, $teamId);
        });
    }
}
