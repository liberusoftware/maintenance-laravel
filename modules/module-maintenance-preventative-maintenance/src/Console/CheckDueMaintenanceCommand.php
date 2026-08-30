<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\PreventativeMaintenance\Console;

use Illuminate\Console\Command;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Models\MaintenancePlan;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Notifications\MaintenanceDueSoonNotification;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Notifications\MaintenanceOverdueNotification;

final class CheckDueMaintenanceCommand extends Command
{
    protected $signature = 'maintenance:check-due {--days=7 : Number of days to look ahead}';

    protected $description = 'Notify assignees about overdue and upcoming preventative maintenance';

    public function handle(): int
    {
        $days = max(0, (int) $this->option('days'));
        $overdue = MaintenancePlan::query()->with('assignedUser')->overdue()->get();
        $upcoming = MaintenancePlan::query()->with('assignedUser')->dueSoon($days)->get();

        foreach ($overdue as $plan) {
            $plan->assignedUser?->notify(new MaintenanceOverdueNotification($plan));
        }
        foreach ($upcoming as $plan) {
            $plan->assignedUser?->notify(new MaintenanceDueSoonNotification($plan, $days));
        }

        $this->info("Checked {$overdue->count()} overdue and {$upcoming->count()} upcoming maintenance plans.");

        return self::SUCCESS;
    }
}
