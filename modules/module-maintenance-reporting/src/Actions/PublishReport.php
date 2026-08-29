<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Report\Actions;

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

        $record->update(['status' => 'published']);

        return $record->refresh();
    }
}
