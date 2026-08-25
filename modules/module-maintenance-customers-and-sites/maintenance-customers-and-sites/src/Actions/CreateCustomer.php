<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Customer;

class CreateCustomer
{
    public function handle(int $teamId, array $attributes): Customer
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $code = strtoupper(trim((string) ($attributes['code'] ?? '')));
        if ($name === '' || $code === '') {
            throw ValidationException::withMessages(['name' => 'Name and code are required.']);
        }
        if (Customer::query()->where('team_id', $teamId)->where('code', $code)->exists()) {
            throw ValidationException::withMessages(['code' => 'The customer code is already in use.']);
        }

        return DB::transaction(fn () => Customer::query()->create(array_merge($attributes, ['team_id' => $teamId, 'name' => $name, 'code' => $code, 'is_active' => $attributes['is_active'] ?? true])));
    }
}
