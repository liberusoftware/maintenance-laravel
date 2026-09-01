<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Actions;

use Liberu\Modules\Maintenance\CustomersAndSites\Models\Location;

final class DeleteLocation
{
    public function handle(int $teamId, Location $location): void
    {
        abort_unless((int) $location->team_id === $teamId, 404);
        $location->delete();
    }
}
