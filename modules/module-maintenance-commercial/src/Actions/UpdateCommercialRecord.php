<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Commercial\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Commercial\Models\CommercialRecord;

final class UpdateCommercialRecord
{
    /** @param array<string, mixed> $attributes */
    public function handle(int $teamId, CommercialRecord $record, array $attributes): CommercialRecord
    {
        abort_unless((int) $record->team_id === $teamId, 404);
        $kind = array_key_exists('kind', $attributes) ? trim((string) $attributes['kind']) : $record->kind;
        $title = array_key_exists('title', $attributes) ? trim((string) $attributes['title']) : $record->title;
        if ($kind === '' || $title === '') {
            throw ValidationException::withMessages(['title' => 'A kind and title are required.']);
        }

        return DB::transaction(function () use ($record, $attributes, $kind, $title): CommercialRecord {
            $record->fill(array_merge($attributes, ['kind' => $kind, 'title' => $title]));
            $record->save();

            return $record->refresh();
        });
    }
}
