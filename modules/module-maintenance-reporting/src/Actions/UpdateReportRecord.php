<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Report\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Report\Models\ReportKind;
use Liberu\Modules\Maintenance\Report\Models\ReportRecord;

final class UpdateReportRecord
{
    /** @param array<string, mixed> $attributes */
    public function handle(int $teamId, ReportRecord $record, array $attributes): ReportRecord
    {
        abort_unless((int) $record->team_id === $teamId, 404);
        if (array_key_exists('status', $attributes) && $attributes['status'] !== $record->status) {
            throw ValidationException::withMessages(['status' => 'Use the publish action to change report status.']);
        }
        $kind = array_key_exists('kind', $attributes) ? trim((string) $attributes['kind']) : $record->kind;
        $title = array_key_exists('title', $attributes) ? trim((string) $attributes['title']) : $record->title;
        if ($kind === '' || ReportKind::tryFrom($kind) === null) {
            throw ValidationException::withMessages(['kind' => 'The report kind is not supported.']);
        }
        if ($title === '') {
            throw ValidationException::withMessages(['title' => 'A title is required.']);
        }

        return DB::transaction(function () use ($record, $attributes, $kind, $title): ReportRecord {
            $record->fill(array_merge($attributes, ['kind' => $kind, 'title' => $title]));
            $record->save();

            return $record->refresh();
        });
    }
}
