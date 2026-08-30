<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\ServiceWindow;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Site;

final class CreateServiceWindow
{
    public function handle(int $teamId, array $attributes): ServiceWindow
    {
        $siteId = (int) ($attributes['site_id'] ?? 0);
        $weekday = (int) ($attributes['weekday'] ?? -1);
        $startsAt = (string) ($attributes['starts_at'] ?? '');
        $endsAt = (string) ($attributes['ends_at'] ?? '');
        if ($siteId < 1 || $weekday < 0 || $weekday > 6 || $startsAt === '' || $endsAt === '' || strtotime($endsAt) <= strtotime($startsAt)) {
            throw ValidationException::withMessages(['starts_at' => 'A valid site, weekday, and non-overlapping time range are required.']);
        }
        if (! Site::query()->whereKey($siteId)->where('team_id', $teamId)->exists()) {
            throw ValidationException::withMessages(['site_id' => 'The site is not available in this team.']);
        }
        $overlap = ServiceWindow::query()->where('team_id', $teamId)->where('site_id', $siteId)->where('weekday', $weekday)->where('starts_at', '<', $endsAt)->where('ends_at', '>', $startsAt)->exists();
        if ($overlap) {
            throw ValidationException::withMessages(['starts_at' => 'The service window overlaps an existing window.']);
        }

        return DB::transaction(fn (): ServiceWindow => ServiceWindow::query()->create(array_merge($attributes, ['team_id' => $teamId, 'site_id' => $siteId, 'weekday' => $weekday, 'timezone' => $attributes['timezone'] ?? 'UTC', 'is_available' => (bool) ($attributes['is_available'] ?? true)]))->refresh());
    }
}
