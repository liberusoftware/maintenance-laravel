<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Report\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Report\Models\ReportRecord;

final class PublishReport
{
    public function execute(int $teamId, ReportRecord $record): ReportRecord
    {
        abort_unless((int) $record->team_id === $teamId, 404);
        if ($record->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Only draft reports can be published.']);
        }

        DB::transaction(function () use ($record): void {
            $metadata = is_array($record->metadata) ? $record->metadata : [];
            $history = is_array($metadata['status_history'] ?? null) ? $metadata['status_history'] : [];
            $history[] = ['from' => $record->status, 'to' => 'published', 'at' => now()->toISOString()];
            $metadata['status_history'] = $history;
            $record->forceFill(['status' => 'published', 'metadata' => $metadata])->save();
        });

        return $record->refresh();
    }
}
