<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Inspections\Models\InspectionTemplate;

final class RemoveInspectionTemplateItem
{
    public function handle(int $teamId, InspectionTemplate $template, string $key): InspectionTemplate
    {
        abort_unless((int) $template->team_id === $teamId, 404);
        $checklist = $template->checklist ?? [];
        if (! array_key_exists($key, $checklist)) throw ValidationException::withMessages(['key' => 'The checklist item does not exist.']);
        return DB::transaction(function () use ($template, $checklist, $key): InspectionTemplate {
            unset($checklist[$key]);
            $template->update(['checklist' => $checklist]);
            return $template->refresh();
        });
    }
}
