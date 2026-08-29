<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrder;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrderDependency;

final class AddWorkOrderDependency
{
    public function handle(int $teamId, WorkOrder $workOrder, WorkOrder $dependsOn): WorkOrderDependency
    {
        abort_unless((int) $workOrder->team_id === $teamId && (int) $dependsOn->team_id === $teamId, 404);
        if ($workOrder->is($dependsOn)) {
            throw ValidationException::withMessages(['depends_on_work_order_id' => 'A work order cannot depend on itself.']);
        }
        if (WorkOrderDependency::query()->where('work_order_id', $workOrder->getKey())->where('depends_on_work_order_id', $dependsOn->getKey())->exists()) {
            throw ValidationException::withMessages(['depends_on_work_order_id' => 'That dependency already exists.']);
        }
        if ($this->dependsOn($dependsOn, $workOrder->getKey())) {
            throw ValidationException::withMessages(['depends_on_work_order_id' => 'Dependencies cannot form a cycle.']);
        }

        return DB::transaction(fn (): WorkOrderDependency => WorkOrderDependency::create([
            'team_id' => $teamId,
            'work_order_id' => $workOrder->getKey(),
            'depends_on_work_order_id' => $dependsOn->getKey(),
        ]));
    }

    private function dependsOn(WorkOrder $current, int $targetId, array $visited = []): bool
    {
        if (in_array($current->getKey(), $visited, true)) {
            return false;
        }
        $visited[] = $current->getKey();
        $dependencies = WorkOrderDependency::query()->where('work_order_id', $current->getKey())->pluck('depends_on_work_order_id');
        if ($dependencies->contains($targetId)) {
            return true;
        }
        foreach ($dependencies as $dependencyId) {
            $dependency = WorkOrder::query()->find($dependencyId);
            if ($dependency !== null && $this->dependsOn($dependency, $targetId, $visited)) {
                return true;
            }
        }

        return false;
    }
}
