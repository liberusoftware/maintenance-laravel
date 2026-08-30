<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Actions;

use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Scheduling\Models\AvailabilityWindow;

final class ValidateEngineerAvailability
{
    /** @param array<string, mixed> $attributes */
    public function handle(int $teamId, array $attributes): void
    {
        if (($attributes['assigned_to'] ?? null) === null) {
            return;
        }

        $windows = AvailabilityWindow::query()
            ->where('team_id', $teamId)
            ->where('user_id', (int) $attributes['assigned_to'])
            ->get();

        // Availability is opt-in so existing schedules remain valid until an engineer
        // has availability configured for the team.
        if ($windows->isEmpty()) {
            return;
        }

        $sourceTimezone = (string) ($attributes['timezone'] ?? config('app.timezone', 'UTC'));
        $startAt = Carbon::parse((string) $attributes['starts_at'], $sourceTimezone);
        $endAt = Carbon::parse((string) $attributes['ends_at'], $sourceTimezone);

        $isCovered = $windows->contains(function (AvailabilityWindow $window) use ($startAt, $endAt): bool {
            if (! $window->is_available) {
                return false;
            }

            $localStart = $startAt->copy()->setTimezone($window->timezone);
            $localEnd = $endAt->copy()->setTimezone($window->timezone);

            return $localStart->dayOfWeek === (int) $window->weekday
                && $localEnd->dayOfWeek === $localStart->dayOfWeek
                && $localStart->format('H:i:s') >= (string) $window->starts_at
                && $localEnd->format('H:i:s') <= (string) $window->ends_at;
        });

        if (! $isCovered) {
            throw ValidationException::withMessages(['assigned_to' => 'The assigned engineer is not available for the scheduled time.']);
        }
    }
}
