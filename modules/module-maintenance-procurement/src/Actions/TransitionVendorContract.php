<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Procurement\Models\VendorContract;

final class TransitionVendorContract
{
    /** @var array<string, list<string>> */
    private const ALLOWED = ['draft' => ['active', 'terminated'], 'active' => ['expired', 'terminated', 'renewed'], 'expired' => ['renewed', 'terminated'], 'renewed' => ['active', 'terminated'], 'terminated' => []];

    public function handle(int $teamId, VendorContract $contract, string $status): VendorContract
    {
        abort_unless((int) $contract->team_id === $teamId, 404);
        if (! in_array($status, self::ALLOWED[$contract->status] ?? [], true)) {
            throw ValidationException::withMessages(['status' => 'That vendor-contract transition is not allowed.']);
        }
        $contract->update(['status' => $status]);

        return $contract->refresh();
    }
}
