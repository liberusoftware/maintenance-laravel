<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Compliance\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Compliance\Models\ComplianceRiskAssessment;

final class CreateComplianceRiskAssessment
{
    public function handle(int $teamId, array $attributes): ComplianceRiskAssessment
    {
        $title = trim((string) ($attributes['title'] ?? ''));
        $score = $attributes['score'] ?? null;
        if ($title === '') {
            throw ValidationException::withMessages(['title' => 'A risk assessment title is required.']);
        }
        if ($score !== null && ((int) $score < 0 || (int) $score > 100)) {
            throw ValidationException::withMessages(['score' => 'The risk score must be between zero and one hundred.']);
        }

        return DB::transaction(fn (): ComplianceRiskAssessment => ComplianceRiskAssessment::create(array_merge($attributes, ['team_id' => $teamId, 'title' => $title]))->refresh());
    }
}
