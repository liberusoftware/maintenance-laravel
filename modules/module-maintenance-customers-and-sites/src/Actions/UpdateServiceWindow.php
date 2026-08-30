<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\ServiceWindow;

final class UpdateServiceWindow
{
    public function handle(int $teamId, ServiceWindow $window, array $attributes): ServiceWindow
    {
        abort_unless((int) $window->team_id === $teamId, 404);
        $weekday = array_key_exists('weekday', $attributes) ? (int) $attributes['weekday'] : (int) $window->weekday;
        $startsAt = (string) ($attributes['starts_at'] ?? $window->starts_at);
        $endsAt = (string) ($attributes['ends_at'] ?? $window->ends_at);
        if ($weekday < 0 || $weekday > 6 || strtotime($endsAt) <= strtotime($startsAt)) {
            throw ValidationException::withMessages(['starts_at' => 'A valid weekday and non-overlapping time range are required.']);
        }
        $overlap = ServiceWindow::query()->where('team_id', $teamId)->where('site_id', $window->site_id)->where('weekday', $weekday)->whereKeyNot($window->getKey())->where('starts_at', '<', $endsAt)->where('ends_at', '>', $startsAt)->exists();
        if ($overlap) {
            throw ValidationException::withMessages(['starts_at' => 'The service window overlaps an existing window.']);
        }

        return DB::transaction(function () use ($window, $attributes, $weekday, $startsAt, $endsAt): ServiceWindow {
            $window->fill(array_merge($attributes, ['weekday' => $weekday, 'starts_at' => $startsAt, 'ends_at' => $endsAt]));
            $window->save();

            return $window->refresh();
        });
    }
}
