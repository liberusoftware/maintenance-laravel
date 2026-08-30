<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Actions;

use Liberu\Modules\Maintenance\Core\Events\StatusDeleted;
use Liberu\Modules\Maintenance\Core\Models\Status;

final class DeleteStatus
{
    public function execute(Status $status): void
    {
        $id = $status->getKey();
        $teamId = $status->team_id;
        $status->delete();
        StatusDeleted::dispatch($id, $teamId);
    }
}
