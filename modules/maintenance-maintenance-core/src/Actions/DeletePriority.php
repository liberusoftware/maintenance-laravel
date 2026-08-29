<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Actions;

use Liberu\Modules\Maintenance\Core\Models\Priority;

final class DeletePriority
{
    public function execute(Priority $priority): void
    {
        $priority->delete();
    }
}
