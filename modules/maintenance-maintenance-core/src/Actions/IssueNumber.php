<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Modules\Maintenance\Core\Models\NumberingSequence;

final class IssueNumber
{
    public function execute(int $teamId, string $documentType): string
    {
        return DB::transaction(function () use ($teamId, $documentType): string {
            $sequence = NumberingSequence::query()
                ->where('team_id', $teamId)
                ->where('document_type', $documentType)
                ->lockForUpdate()
                ->first();
            if ($sequence === null) {
                $sequence = NumberingSequence::query()->create([
                    'team_id' => $teamId,
                    'document_type' => $documentType,
                    'prefix' => (string) config('maintenance-core.numbering.default_prefix', 'WO-'),
                    'next_number' => 2,
                    'padding' => (int) config('maintenance-core.numbering.default_padding', 6),
                ]);

                return $sequence->prefix.str_pad('1', $sequence->padding, '0', STR_PAD_LEFT);
            }
            $issued = $sequence->next_number;
            $sequence->increment('next_number');

            return $sequence->prefix.str_pad((string) $issued, $sequence->padding, '0', STR_PAD_LEFT);
        });
    }
}
