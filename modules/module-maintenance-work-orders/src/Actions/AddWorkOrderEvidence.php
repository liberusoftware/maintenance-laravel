<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrder;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrderEvidence;

final class AddWorkOrderEvidence
{
    /** @param array<string, mixed> $attributes */
    public function handle(int $teamId, WorkOrder $workOrder, array $attributes): WorkOrderEvidence
    {
        abort_unless((int) $workOrder->team_id === $teamId, 404);
        $kind = trim((string) ($attributes['kind'] ?? ''));
        $label = trim((string) ($attributes['label'] ?? ''));
        $reference = trim((string) ($attributes['reference'] ?? ''));
        if ($kind === '' || $label === '' || $reference === '') {
            throw ValidationException::withMessages(['reference' => 'Evidence kind, label, and reference are required.']);
        }

        return DB::transaction(fn (): WorkOrderEvidence => WorkOrderEvidence::create(array_merge($attributes, [
            'team_id' => $teamId,
            'work_order_id' => $workOrder->getKey(),
            'kind' => $kind,
            'label' => $label,
            'reference' => $reference,
        ])));
    }
}
