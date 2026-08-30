<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Actions;

use Liberu\Modules\Maintenance\Core\Events\PriorityDeleted;
use Liberu\Modules\Maintenance\Core\Models\Priority;

final class DeletePriority
{
    public function execute(Priority $priority): void
    {
        $id = $priority->getKey();
        $teamId = $priority->team_id;
        $priority->delete();
        PriorityDeleted::dispatch($id, $teamId);
    }
}
