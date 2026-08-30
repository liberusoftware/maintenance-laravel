<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Location;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Site;

final class CreateLocation
{
    public function handle(int $teamId, array $attributes): Location
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $siteId = (int) ($attributes['site_id'] ?? 0);
        if ($name === '' || $siteId < 1) {
            throw ValidationException::withMessages(['name' => 'Name and site are required.']);
        }
        if (! Site::query()->whereKey($siteId)->where('team_id', $teamId)->exists()) {
            throw ValidationException::withMessages(['site_id' => 'The site is not available in this team.']);
        }
        if (Location::query()->where('site_id', $siteId)->where('name', $name)->exists()) {
            throw ValidationException::withMessages(['name' => 'The location name is already in use for this site.']);
        }

        return DB::transaction(fn (): Location => Location::query()->create(array_merge($attributes, ['team_id' => $teamId, 'site_id' => $siteId, 'name' => $name, 'is_active' => (bool) ($attributes['is_active'] ?? true)]))->refresh());
    }
}
