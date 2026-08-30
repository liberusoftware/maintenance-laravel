<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Contact;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Customer;

final class UpdateContact
{
    public function handle(int $teamId, Contact $contact, array $attributes): Contact
    {
        abort_unless((int) $contact->team_id === $teamId, 404);
        $name = array_key_exists('name', $attributes) ? trim((string) $attributes['name']) : $contact->name;
        $customerId = array_key_exists('customer_id', $attributes) ? (int) $attributes['customer_id'] : (int) $contact->customer_id;
        if ($name === '' || $customerId < 1) {
            throw ValidationException::withMessages(['name' => 'Name and customer are required.']);
        }
        if (! Customer::query()->whereKey($customerId)->where('team_id', $teamId)->exists()) {
            throw ValidationException::withMessages(['customer_id' => 'The customer is not available in this team.']);
        }

        return DB::transaction(function () use ($teamId, $contact, $attributes, $name, $customerId): Contact {
            if (($attributes['is_primary'] ?? $contact->is_primary) === true) {
                Contact::query()->where('team_id', $teamId)->where('customer_id', $customerId)->whereKeyNot($contact->getKey())->update(['is_primary' => false]);
            }
            $contact->fill(array_merge($attributes, ['name' => $name, 'customer_id' => $customerId]));
            $contact->save();

            return $contact->refresh();
        });
    }
}
