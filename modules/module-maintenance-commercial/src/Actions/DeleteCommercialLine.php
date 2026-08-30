<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Commercial\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Modules\Maintenance\Commercial\Models\CommercialLine;
use Liberu\Modules\Maintenance\Commercial\Models\CommercialRecord;

final class DeleteCommercialLine
{
    public function __construct(private readonly SyncCommercialTotal $syncTotal) {}

    public function handle(int $teamId, CommercialRecord $record, CommercialLine $line): void
    {
        abort_unless((int) $record->team_id === $teamId && (int) $line->team_id === $teamId && (int) $line->commercial_record_id === (int) $record->getKey(), 404);
        DB::transaction(function () use ($record, $line): void {
            $line->delete();
            $this->syncTotal->handle($record);
        });
    }
}
