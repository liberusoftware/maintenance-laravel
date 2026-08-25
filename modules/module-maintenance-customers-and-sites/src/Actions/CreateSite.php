<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Customer;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Site;

class CreateSite
{
    public function handle(int $teamId, array $attributes): Site
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $code = strtoupper(trim((string) ($attributes['code'] ?? '')));
        $customerId = (int) ($attributes['customer_id'] ?? 0);
        if ($name === '' || $code === '' || $customerId < 1) {
            throw ValidationException::withMessages(['name' => 'Name, code, and customer are required.']);
        }
        if (! Customer::query()->whereKey($customerId)->where('team_id', $teamId)->exists()) {
            throw ValidationException::withMessages(['customer_id' => 'The customer is not available in this team.']);
        }
        if (Site::query()->where('team_id', $teamId)->where('code', $code)->exists()) {
            throw ValidationException::withMessages(['code' => 'The site code is already in use.']);
        }

        return DB::transaction(fn () => Site::query()->create(array_merge($attributes, ['team_id' => $teamId, 'name' => $name, 'code' => $code, 'is_active' => $attributes['is_active'] ?? true])));
    }
}
