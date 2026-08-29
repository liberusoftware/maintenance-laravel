<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Modules\Maintenance\Procurement\Models\PurchaseRequest;

final class DeletePurchaseRequest
{
    public function handle(int $teamId, PurchaseRequest $request): void
    {
        abort_unless((int) $request->team_id === $teamId, 404);
        DB::transaction(static fn (): bool => (bool) $request->delete());
    }
}
