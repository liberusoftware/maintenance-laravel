<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Compliance\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Compliance\Models\ComplianceRequirement;

final class CreateComplianceRequirement
{
    public function handle(int $teamId, array $attributes): ComplianceRequirement
    {
        $code = trim((string) ($attributes['code'] ?? ''));
        $title = trim((string) ($attributes['title'] ?? ''));
        if ($code === '' || $title === '') {
            throw ValidationException::withMessages(['requirement' => 'A requirement code and title are required.']);
        }
        if (ComplianceRequirement::query()->where('team_id', $teamId)->where('code', $code)->exists()) {
            throw ValidationException::withMessages(['code' => 'That requirement code already exists.']);
        }

        return DB::transaction(fn (): ComplianceRequirement => ComplianceRequirement::create(array_merge($attributes, ['team_id' => $teamId, 'code' => $code, 'title' => $title]))->refresh());
    }
}
