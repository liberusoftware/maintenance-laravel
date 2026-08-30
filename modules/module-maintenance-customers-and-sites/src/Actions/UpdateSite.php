<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Customer;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Site;

final class UpdateSite
{
    /** @param array<string, mixed> $attributes */
    public function handle(int $teamId, Site $site, array $attributes): Site
    {
        abort_unless((int) $site->team_id === $teamId, 404);
        $name = array_key_exists('name', $attributes) ? trim((string) $attributes['name']) : $site->name;
        $code = array_key_exists('code', $attributes) ? strtoupper(trim((string) $attributes['code'])) : $site->code;
        $customerId = array_key_exists('customer_id', $attributes) ? (int) $attributes['customer_id'] : (int) $site->customer_id;
        if ($name === '' || $code === '' || $customerId < 1) {
            throw ValidationException::withMessages(['name' => 'Name, code, and customer are required.']);
        }
        if (! Customer::query()->whereKey($customerId)->where('team_id', $teamId)->exists()) {
            throw ValidationException::withMessages(['customer_id' => 'The customer is not available in this team.']);
        }
        if (Site::query()->where('team_id', $teamId)->where('code', $code)->whereKeyNot($site->getKey())->exists()) {
            throw ValidationException::withMessages(['code' => 'The site code is already in use.']);
        }

        return DB::transaction(function () use ($site, $attributes, $name, $code, $customerId): Site {
            $site->fill(array_merge($attributes, ['name' => $name, 'code' => $code, 'customer_id' => $customerId]));
            $site->save();

            return $site->refresh();
        });
    }
}
