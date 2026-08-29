<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Site;

final class DeleteSite
{
    public function handle(int $teamId, Site $site): void
    {
        abort_unless((int) $site->team_id === $teamId, 404);
        DB::transaction(static fn (): bool => (bool) $site->delete());
    }
}
