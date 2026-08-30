<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Commercial\Actions;

use Liberu\Modules\Maintenance\Commercial\Models\CommercialRecord;

final class SyncCommercialTotal
{
    public function handle(CommercialRecord $record): CommercialRecord
    {
        $record->forceFill(['amount' => $record->lines()->sum('line_total')])->save();

        return $record->refresh();
    }
}
