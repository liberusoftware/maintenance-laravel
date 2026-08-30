<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Actions;

use Liberu\Modules\Maintenance\CustomersAndSites\Models\ServiceWindow;

final class DeleteServiceWindow
{
    public function handle(int $teamId, ServiceWindow $window): void
    {
        abort_unless((int) $window->team_id === $teamId, 404);
        $window->delete();
    }
}
