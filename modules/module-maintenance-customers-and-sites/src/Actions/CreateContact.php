<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Contact;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Customer;

final class CreateContact
{
    public function handle(int $teamId, array $attributes): Contact
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $customerId = (int) ($attributes['customer_id'] ?? 0);
        if ($name === '' || $customerId < 1) {
            throw ValidationException::withMessages(['name' => 'Name and customer are required.']);
        }
        if (! Customer::query()->whereKey($customerId)->where('team_id', $teamId)->exists()) {
            throw ValidationException::withMessages(['customer_id' => 'The customer is not available in this team.']);
        }

        return DB::transaction(function () use ($teamId, $attributes, $name, $customerId): Contact {
            if (($attributes['is_primary'] ?? false) === true) {
                Contact::query()->where('team_id', $teamId)->where('customer_id', $customerId)->update(['is_primary' => false]);
            }

            return Contact::query()->create(array_merge($attributes, ['team_id' => $teamId, 'customer_id' => $customerId, 'name' => $name, 'is_primary' => (bool) ($attributes['is_primary'] ?? false), 'is_active' => (bool) ($attributes['is_active'] ?? true)]))->refresh();
        });
    }
}
