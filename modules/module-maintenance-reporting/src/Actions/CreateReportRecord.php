<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Report\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Report\Models\ReportKind;
use Liberu\Modules\Maintenance\Report\Models\ReportRecord;

class CreateReportRecord
{
    public function handle(int $teamId, array $attributes): ReportRecord
    {
        $kind = trim((string) ($attributes['kind'] ?? ''));
        $title = trim((string) ($attributes['title'] ?? ''));
        if ($kind === '' || ReportKind::tryFrom($kind) === null) {
            throw ValidationException::withMessages(['kind' => 'The report kind is not supported.']);
        }
        if ($title === '') {
            throw ValidationException::withMessages(['title' => 'A title is required.']);
        }

        return DB::transaction(fn () => ReportRecord::create(array_merge($attributes, ['team_id' => $teamId, 'kind' => $kind, 'title' => $title, 'status' => $attributes['status'] ?? 'draft'])));
    }
}
