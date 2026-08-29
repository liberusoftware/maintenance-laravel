<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Procurement\Models\VendorContract;

final class CreateVendorContract
{
    /** @param array<string, mixed> $attributes */
    public function handle(int $teamId, array $attributes): VendorContract
    {
        $vendor = trim((string) ($attributes['vendor_name'] ?? ''));
        $number = trim((string) ($attributes['contract_number'] ?? ''));
        $title = trim((string) ($attributes['title'] ?? ''));
        if ($vendor === '' || $number === '' || $title === '') {
            throw ValidationException::withMessages(['title' => 'Vendor, contract number, and title are required.']);
        }
        if (VendorContract::query()->where('team_id', $teamId)->where('contract_number', $number)->exists()) {
            throw ValidationException::withMessages(['contract_number' => 'The contract number is already in use.']);
        }
        if (strtotime((string) ($attributes['end_date'] ?? '')) < strtotime((string) ($attributes['start_date'] ?? ''))) {
            throw ValidationException::withMessages(['end_date' => 'The end date must be on or after the start date.']);
        }

        return DB::transaction(fn (): VendorContract => VendorContract::create(array_merge($attributes, ['team_id' => $teamId, 'vendor_name' => $vendor, 'contract_number' => $number, 'title' => $title, 'status' => $attributes['status'] ?? 'draft'])));
    }
}
