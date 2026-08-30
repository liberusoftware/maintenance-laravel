<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Compliance\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Compliance\Models\ComplianceRecord;

final class UpdateComplianceRecord
{
    /** @param array<string, mixed> $attributes */
    public function handle(int $teamId, ComplianceRecord $record, array $attributes): ComplianceRecord
    {
        abort_unless((int) $record->team_id === $teamId, 404);
        $kind = array_key_exists('kind', $attributes) ? trim((string) $attributes['kind']) : $record->kind;
        $title = array_key_exists('title', $attributes) ? trim((string) $attributes['title']) : $record->title;
        if ($kind === '' || $title === '') {
            throw ValidationException::withMessages(['title' => 'A kind and title are required.']);
        }

        return DB::transaction(function () use ($record, $attributes, $kind, $title): ComplianceRecord {
            $record->fill(array_merge($attributes, ['kind' => $kind, 'title' => $title]));
            $record->save();

            return $record->refresh();
        });
    }
}
