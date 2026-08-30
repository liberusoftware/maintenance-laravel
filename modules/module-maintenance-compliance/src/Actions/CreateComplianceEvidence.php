<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Compliance\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Compliance\Models\ComplianceEvidence;
use Liberu\Modules\Maintenance\Compliance\Models\ComplianceRecord;

final class CreateComplianceEvidence
{
    public function handle(int $teamId, ComplianceRecord $record, array $attributes): ComplianceEvidence
    {
        abort_unless((int) $record->team_id === $teamId, 404);
        $kind = trim((string) ($attributes['kind'] ?? ''));
        $label = trim((string) ($attributes['label'] ?? ''));
        if ($kind === '' || $label === '') {
            throw ValidationException::withMessages(['label' => 'Evidence kind and label are required.']);
        }

        return DB::transaction(fn (): ComplianceEvidence => ComplianceEvidence::query()->create(array_merge($attributes, ['team_id' => $teamId, 'compliance_record_id' => $record->getKey(), 'kind' => $kind, 'label' => $label, 'recorded_by' => $attributes['recorded_by'] ?? null]))->refresh());
    }
}
