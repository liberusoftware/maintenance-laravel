<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Procurement\Models\VendorContract;

final class UpdateVendorContract
{
    /** @param array<string, mixed> $attributes */
    public function handle(int $teamId, VendorContract $contract, array $attributes): VendorContract
    {
        abort_unless((int) $contract->team_id === $teamId, 404);
        $start = $attributes['start_date'] ?? $contract->start_date;
        $end = $attributes['end_date'] ?? $contract->end_date;
        if (strtotime((string) $end) < strtotime((string) $start)) {
            throw ValidationException::withMessages(['end_date' => 'The end date must be on or after the start date.']);
        }
        if (array_key_exists('contract_number', $attributes) && VendorContract::query()->where('team_id', $teamId)->where('contract_number', $attributes['contract_number'])->whereKeyNot($contract)->exists()) {
            throw ValidationException::withMessages(['contract_number' => 'The contract number is already in use.']);
        }

        return DB::transaction(function () use ($contract, $attributes, $start, $end): VendorContract {
            $contract->fill(array_merge($attributes, ['start_date' => $start, 'end_date' => $end]));
            $contract->save();

            return $contract->refresh();
        });
    }
}
