<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Portal\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Modules\Maintenance\Portal\Models\PortalRecord;

final class DeletePortalRecord
{
    public function handle(int $teamId, PortalRecord $record): void
    {
        abort_unless((int) $record->team_id === $teamId, 404);
        DB::transaction(static fn (): bool => (bool) $record->delete());
    }
}
