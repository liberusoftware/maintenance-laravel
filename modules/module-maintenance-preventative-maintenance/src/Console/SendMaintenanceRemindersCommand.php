<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\PreventativeMaintenance\Console;

use Illuminate\Console\Command;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Models\MaintenancePlan;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Notifications\MaintenanceReminderNotification;

final class SendMaintenanceRemindersCommand extends Command
{
    protected $signature = 'maintenance:send-reminders {--days=3 : Number of days before due date}';
    protected $description = 'Send reminders for preventative maintenance due on a target date';

    public function handle(): int
    {
        $days = max(0, (int) $this->option('days'));
        $target = now()->addDays($days);
        $plans = MaintenancePlan::query()->with('assignedUser')->active()->whereDate('next_due_at', $target)->get();

        foreach ($plans as $plan) {
            $plan->assignedUser?->notify(new MaintenanceReminderNotification($plan, $days));
        }

        $this->info("Sent {$plans->filter(fn (MaintenancePlan $plan): bool => $plan->assignedUser !== null)->count()} maintenance reminder(s).");
        return self::SUCCESS;
    }
}
