<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Customer;

final class UpdateCustomer
{
    /** @param array<string, mixed> $attributes */
    public function handle(int $teamId, Customer $customer, array $attributes): Customer
    {
        abort_unless((int) $customer->team_id === $teamId, 404);
        $name = array_key_exists('name', $attributes) ? trim((string) $attributes['name']) : $customer->name;
        $code = array_key_exists('code', $attributes) ? strtoupper(trim((string) $attributes['code'])) : $customer->code;
        if ($name === '' || $code === '') {
            throw ValidationException::withMessages(['name' => 'Name and code are required.']);
        }
        if (Customer::query()->where('team_id', $teamId)->where('code', $code)->whereKeyNot($customer->getKey())->exists()) {
            throw ValidationException::withMessages(['code' => 'The customer code is already in use.']);
        }

        return DB::transaction(function () use ($customer, $attributes, $name, $code): Customer {
            $customer->fill(array_merge($attributes, ['name' => $name, 'code' => $code]));
            $customer->save();

            return $customer->refresh();
        });
    }
}
