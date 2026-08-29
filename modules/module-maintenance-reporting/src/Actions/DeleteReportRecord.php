<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Report\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Modules\Maintenance\Report\Models\ReportRecord;

final class DeleteReportRecord
{
    public function handle(int $teamId, ReportRecord $record): void
    {
        abort_unless((int) $record->team_id === $teamId, 404);
        DB::transaction(static fn (): bool => (bool) $record->delete());
    }
}
