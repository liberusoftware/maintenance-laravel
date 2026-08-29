<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Actions;

use Liberu\Modules\Maintenance\Core\Models\Status;

final class DeleteStatus
{
    public function execute(Status $status): void
    {
        $status->delete();
    }
}
