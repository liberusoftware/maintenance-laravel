<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Inspections\Models\InspectionTemplate;

final class UpdateInspectionTemplateItem
{
    public function handle(int $teamId, InspectionTemplate $template, string $key, array $changes): InspectionTemplate
    {
        abort_unless((int) $template->team_id === $teamId, 404);
        $checklist = $template->checklist ?? [];
        if (! array_key_exists($key, $checklist)) {
            throw ValidationException::withMessages(['key' => 'The checklist item does not exist.']);
        }
        $definition = array_merge($checklist[$key], $changes);
        if (! in_array($definition['type'] ?? 'string', ['numeric', 'number', 'boolean', 'bool', 'choice', 'select', 'string', 'text'], true)) {
            throw ValidationException::withMessages(['type' => 'The checklist item type is not supported.']);
        }
        if (in_array($definition['type'] ?? null, ['choice', 'select'], true) && ! is_array($definition['options'] ?? null)) {
            throw ValidationException::withMessages(['options' => 'Choice checklist items require options.']);
        }

        return DB::transaction(function () use ($template, $checklist, $key, $definition): InspectionTemplate {
            $checklist[$key] = $definition;
            $template->update(['checklist' => $checklist]);

            return $template->refresh();
        });
    }
}
