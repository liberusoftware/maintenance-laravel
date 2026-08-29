<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Commercial\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Modules\Maintenance\Commercial\Models\CommercialRecord;

final class DeleteCommercialRecord
{
    public function handle(int $teamId, CommercialRecord $record): void
    {
        abort_unless((int) $record->team_id === $teamId, 404);
        DB::transaction(static fn (): bool => (bool) $record->delete());
    }
}
