<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Compliance\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Compliance\Models\ComplianceRecord;
use Liberu\Modules\Maintenance\Compliance\Models\CorrectiveAction;

final class CreateCorrectiveAction
{
    public function handle(int $teamId, ComplianceRecord $record, array $attributes): CorrectiveAction
    {
        abort_unless((int) $record->team_id === $teamId, 404);
        $title = trim((string) ($attributes['title'] ?? ''));
        if ($title === '') {
            throw ValidationException::withMessages(['title' => 'A corrective-action title is required.']);
        }

        return DB::transaction(fn (): CorrectiveAction => CorrectiveAction::query()->create(array_merge($attributes, ['team_id' => $teamId, 'compliance_record_id' => $record->getKey(), 'title' => $title, 'status' => $attributes['status'] ?? 'open']))->refresh());
    }
}
