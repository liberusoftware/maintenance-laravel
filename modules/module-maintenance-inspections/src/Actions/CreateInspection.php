<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Inspections\Models\Inspection;

class CreateInspection
{
    public function __construct(private readonly ValidateInspectionChecklist $validateChecklist) {}

    public function handle(int $teamId, array $attributes): Inspection
    {
        $title = trim((string) ($attributes['title'] ?? ''));
        if ($title === '') {
            throw ValidationException::withMessages(['title' => 'An inspection title is required.']);
        }
        $this->validateChecklist->handle($teamId, $attributes['template_key'] ?? null, $attributes['readings'] ?? null);

        return DB::transaction(fn () => Inspection::create(array_merge($attributes, ['team_id' => $teamId, 'title' => $title, 'status' => $attributes['status'] ?? 'draft', 'outcome' => $attributes['outcome'] ?? 'pending', 'inspector_id' => $attributes['inspector_id'] ?? null])));
    }
}
