<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Compliance\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Modules\Maintenance\Compliance\Models\ComplianceRecord;

final class DeleteComplianceRecord
{
    public function handle(int $teamId, ComplianceRecord $record): void
    {
        abort_unless((int) $record->team_id === $teamId, 404);
        DB::transaction(static fn (): bool => (bool) $record->delete());
    }
}
