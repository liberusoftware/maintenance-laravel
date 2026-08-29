<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Procurement\Models\PurchaseRequest;

final class RejectPurchaseRequest
{
    public function handle(int $teamId, PurchaseRequest $request, int $approverId, ?string $reason = null): PurchaseRequest
    {
        abort_unless((int) $request->team_id === $teamId, 404);
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Only pending requests can be rejected.']);
        }
        if ((int) $request->requested_by === $approverId) {
            throw ValidationException::withMessages(['rejected_by' => 'The requester cannot reject their own request.']);
        }

        DB::transaction(function () use ($request, $approverId, $reason): void {
            $metadata = is_array($request->metadata) ? $request->metadata : [];
            if ($reason !== null && trim($reason) !== '') {
                $metadata['rejection_reason'] = trim($reason);
            }
            $history = is_array($metadata['status_history'] ?? null) ? $metadata['status_history'] : [];
            $history[] = ['from' => $request->status, 'to' => 'rejected', 'actor_id' => $approverId, 'at' => now()->toISOString()];
            $metadata['status_history'] = $history;
            $request->forceFill(['status' => 'rejected', 'approved_by' => $approverId, 'metadata' => $metadata])->save();
        });

        return $request->refresh();
    }
}
