<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Inspections\Models\Inspection;

final class UpdateInspection
{
    public function __construct(private readonly ValidateInspectionChecklist $validateChecklist) {}

    /** @param array<string, mixed> $attributes */
    public function handle(int $teamId, Inspection $inspection, array $attributes): Inspection
    {
        abort_unless((int) $inspection->team_id === $teamId, 404);
        foreach (['status', 'outcome'] as $transitionField) {
            if (array_key_exists($transitionField, $attributes) && $attributes[$transitionField] !== $inspection->{$transitionField}) {
                throw ValidationException::withMessages([$transitionField => 'Use the inspection completion action to change this field.']);
            }
        }
        $title = array_key_exists('title', $attributes) ? trim((string) $attributes['title']) : $inspection->title;
        if ($title === '') {
            throw ValidationException::withMessages(['title' => 'An inspection title is required.']);
        }
        $this->validateChecklist->handle($teamId, $attributes['template_key'] ?? $inspection->template_key, $attributes['readings'] ?? $inspection->readings);

        return DB::transaction(function () use ($inspection, $attributes, $title): Inspection {
            $inspection->fill(array_merge($attributes, ['title' => $title]));
            $inspection->save();

            return $inspection->refresh();
        });
    }
}
