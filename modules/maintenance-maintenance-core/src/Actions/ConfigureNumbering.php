<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Core\Models\NumberingSequence;

final class ConfigureNumbering
{
    public function execute(int $teamId, string $documentType, string $prefix, int $padding = 6): NumberingSequence
    {
        $documentType = trim($documentType);
        $prefix = trim($prefix);
        if ($documentType === '' || $prefix === '' || $padding < 1 || $padding > 20) {
            throw ValidationException::withMessages(['document_type' => 'A document type, prefix, and valid padding are required.']);
        }

        return DB::transaction(fn (): NumberingSequence => NumberingSequence::query()->updateOrCreate(
            ['team_id' => $teamId, 'document_type' => $documentType],
            ['prefix' => $prefix, 'padding' => $padding],
        )->refresh());
    }
}
