<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Actions;

use Liberu\Modules\Maintenance\Procurement\Models\VendorContract;

final class DeleteVendorContract
{
    public function handle(int $teamId, VendorContract $contract): void
    {
        abort_unless((int) $contract->team_id === $teamId, 404);
        $contract->delete();
    }
}
