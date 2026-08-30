<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\PreventativeMaintenance\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Models\MaintenancePlan;

final class MaintenanceDueSoonNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public MaintenancePlan $plan, public int $daysUntilDue = 7) {}

    public function via(object $notifiable): array { return ['mail', 'database']; }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Upcoming Maintenance: {$this->plan->name}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$this->plan->name} is due in {$this->daysUntilDue} days.")
            ->line('Due date: '.optional($this->plan->next_due_at)->format('Y-m-d'));
    }

    public function toDatabase(object $notifiable): array
    {
        return ['maintenance_plan_id' => $this->plan->getKey(), 'title' => "Upcoming: {$this->plan->name}", 'days_until_due' => $this->daysUntilDue, 'due_at' => $this->plan->next_due_at];
    }
}
