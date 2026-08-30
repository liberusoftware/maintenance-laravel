<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Commercial\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Commercial\Models\CommercialLine;
use Liberu\Modules\Maintenance\Commercial\Models\CommercialRecord;

final class UpdateCommercialLine
{
    public function __construct(private readonly SyncCommercialTotal $syncTotal) {}

    public function handle(int $teamId, CommercialRecord $record, CommercialLine $line, array $attributes): CommercialLine
    {
        abort_unless((int) $record->team_id === $teamId && (int) $line->team_id === $teamId && (int) $line->commercial_record_id === (int) $record->getKey(), 404);
        $description = array_key_exists('description', $attributes) ? trim((string) $attributes['description']) : $line->description;
        $quantity = array_key_exists('quantity', $attributes) ? (float) $attributes['quantity'] : (float) $line->quantity;
        $unitPrice = array_key_exists('unit_price', $attributes) ? (float) $attributes['unit_price'] : (float) $line->unit_price;
        if ($description === '' || $quantity <= 0 || $unitPrice < 0) {
            throw ValidationException::withMessages(['description' => 'A description, positive quantity, and non-negative unit price are required.']);
        }

        return DB::transaction(function () use ($record, $line, $attributes, $description, $quantity, $unitPrice): CommercialLine {
            $line->fill(array_merge($attributes, ['description' => $description, 'quantity' => $quantity, 'unit_price' => $unitPrice, 'line_total' => round($quantity * $unitPrice, 2)]));
            $line->save();
            $this->syncTotal->handle($record);

            return $line->refresh();
        });
    }
}
