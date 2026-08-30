<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Commercial\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Commercial\Models\CommercialLine;
use Liberu\Modules\Maintenance\Commercial\Models\CommercialRecord;

final class CreateCommercialLine
{
    public function __construct(private readonly SyncCommercialTotal $syncTotal) {}

    public function handle(int $teamId, CommercialRecord $record, array $attributes): CommercialLine
    {
        abort_unless((int) $record->team_id === $teamId, 404);
        $description = trim((string) ($attributes['description'] ?? ''));
        $quantity = (float) ($attributes['quantity'] ?? 1);
        $unitPrice = (float) ($attributes['unit_price'] ?? 0);
        if ($description === '' || $quantity <= 0 || $unitPrice < 0) {
            throw ValidationException::withMessages(['description' => 'A description, positive quantity, and non-negative unit price are required.']);
        }

        return DB::transaction(function () use ($teamId, $record, $attributes, $description, $quantity, $unitPrice): CommercialLine {
            $line = CommercialLine::query()->create(array_merge($attributes, ['team_id' => $teamId, 'commercial_record_id' => $record->getKey(), 'description' => $description, 'quantity' => $quantity, 'unit_price' => $unitPrice, 'line_total' => round($quantity * $unitPrice, 2)]));
            $this->syncTotal->handle($record);

            return $line->refresh();
        });
    }
}
