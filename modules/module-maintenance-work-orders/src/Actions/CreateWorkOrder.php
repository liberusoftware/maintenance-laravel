<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Core\Actions\IssueNumber;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrder;

class CreateWorkOrder
{
    public function handle(int $teamId, array $attributes): WorkOrder
    {
        $title = trim((string) ($attributes['title'] ?? ''));
        if ($title === '') {
            throw ValidationException::withMessages(['title' => 'A title is required.']);
        }

        return DB::transaction(fn () => WorkOrder::query()->create(array_merge($attributes, ['team_id' => $teamId, 'number' => app(IssueNumber::class)->execute($teamId, 'work-order'), 'title' => $title, 'status' => 'requested', 'priority' => $attributes['priority'] ?? 'normal'])));
    }
}
