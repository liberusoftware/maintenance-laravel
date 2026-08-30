<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Inspections\Models\InspectionTemplate;

final class AddInspectionTemplateItem
{
    public function handle(int $teamId, InspectionTemplate $template, string $key, array $definition): InspectionTemplate
    {
        $this->assertOwnership($teamId, $template);
        $key = trim($key);
        if ($key === '' || array_key_exists($key, $template->checklist ?? [])) {
            throw ValidationException::withMessages(['key' => 'The checklist item key must be unique within this template.']);
        }
        $this->validateDefinition($definition);

        return DB::transaction(function () use ($template, $key, $definition): InspectionTemplate {
            $checklist = $template->checklist ?? [];
            $checklist[$key] = $definition;
            $template->update(['checklist' => $checklist]);
            return $template->refresh();
        });
    }

    /** @param array<string, mixed> $definition */
    private function validateDefinition(array $definition): void
    {
        if (! in_array($definition['type'] ?? 'string', ['numeric', 'number', 'boolean', 'bool', 'choice', 'select', 'string', 'text'], true)) {
            throw ValidationException::withMessages(['type' => 'The checklist item type is not supported.']);
        }
        if (in_array($definition['type'] ?? null, ['choice', 'select'], true) && ! is_array($definition['options'] ?? null)) {
            throw ValidationException::withMessages(['options' => 'Choice checklist items require options.']);
        }
    }

    private function assertOwnership(int $teamId, InspectionTemplate $template): void { abort_unless((int) $template->team_id === $teamId, 404); }
}
