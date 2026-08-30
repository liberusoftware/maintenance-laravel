<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\PreventativeMaintenance\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Models\MaintenancePlan;
use Liberu\Modules\Maintenance\WorkOrders\Actions\CreateWorkOrder;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrder;

final class GenerateWorkOrderFromPlan
{
    public function __construct(private readonly CreateWorkOrder $createWorkOrder) {}

    public function handle(int $teamId, MaintenancePlan $plan): WorkOrder
    {
        abort_unless((int) $plan->team_id === $teamId, 404);
        if (! $plan->is_active) throw ValidationException::withMessages(['is_active' => 'Inactive plans cannot generate work orders.']);

        return DB::transaction(function () use ($teamId, $plan): WorkOrder {
            $existing = $plan->workOrders()->whereNotIn('status', ['completed', 'cancelled'])->latest()->first();
            if ($existing !== null) return $existing;

            return $this->createWorkOrder->handle($teamId, array_filter([
                'title' => $plan->name,
                'description' => $plan->description,
                'equipment_id' => $plan->equipment_id,
                'assigned_to' => $plan->assigned_to,
                'due_date' => $plan->next_due_at,
                'estimated_minutes' => $plan->estimated_duration,
                'priority' => $plan->priority,
                'maintenance_plan_id' => $plan->getKey(),
                'checklist_id' => $plan->checklist_id,
                'notes' => $plan->instructions,
            ], static fn (mixed $value): bool => $value !== null));
        });
    }
}
