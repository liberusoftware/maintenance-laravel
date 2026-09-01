<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Modules\Maintenance\Inspections\Models\InspectionTemplate;

final class DuplicateInspectionTemplate
{
    public function handle(int $teamId, InspectionTemplate $template, ?string $name = null, ?string $key = null): InspectionTemplate
    {
        abort_unless((int) $template->team_id === $teamId, 404);

        return DB::transaction(function () use ($template, $teamId, $name, $key): InspectionTemplate {
            $copy = $template->replicate();
            $copy->team_id = $teamId;
            $copy->name = $name ?: $template->name.' (Copy)';
            $copy->key = $key ?: $template->key.'-copy-'.strtolower((string) str()->random(6));
            $copy->is_active = false;
            $copy->save();

            return $copy->refresh();
        });
    }
}
