<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Inspections\Models\InspectionTemplate;

final class CreateInspectionTemplate
{
    public function handle(int $teamId, array $attributes): InspectionTemplate
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $key = trim((string) ($attributes['key'] ?? ''));
        if ($name === '' || $key === '' || ! is_array($attributes['checklist'] ?? null)) {
            throw ValidationException::withMessages(['checklist' => 'A name, key, and checklist definition are required.']);
        }

        return DB::transaction(fn (): InspectionTemplate => InspectionTemplate::create(array_merge($attributes, ['team_id' => $teamId, 'name' => $name, 'key' => $key, 'is_active' => $attributes['is_active'] ?? true])));
    }
}
