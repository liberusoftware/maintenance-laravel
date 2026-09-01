<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Location;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Site;

final class UpdateLocation
{
    public function handle(int $teamId, Location $location, array $attributes): Location
    {
        abort_unless((int) $location->team_id === $teamId, 404);
        $name = array_key_exists('name', $attributes) ? trim((string) $attributes['name']) : $location->name;
        $siteId = array_key_exists('site_id', $attributes) ? (int) $attributes['site_id'] : (int) $location->site_id;
        if ($name === '' || $siteId < 1) {
            throw ValidationException::withMessages(['name' => 'Name and site are required.']);
        }
        if (! Site::query()->whereKey($siteId)->where('team_id', $teamId)->exists()) {
            throw ValidationException::withMessages(['site_id' => 'The site is not available in this team.']);
        }
        if (Location::query()->where('site_id', $siteId)->where('name', $name)->whereKeyNot($location->getKey())->exists()) {
            throw ValidationException::withMessages(['name' => 'The location name is already in use for this site.']);
        }

        return DB::transaction(function () use ($location, $attributes, $name, $siteId): Location {
            $location->fill(array_merge($attributes, ['name' => $name, 'site_id' => $siteId]));
            $location->save();

            return $location->refresh();
        });
    }
}
