<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Procurement\Models\PurchaseRequest;

class ApprovePurchaseRequest
{
    public function handle(int $teamId, PurchaseRequest $request, int $approverId): PurchaseRequest
    {
        if ((int) $request->team_id !== $teamId) {
            abort(404);
        }
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Only pending requests can be approved.']);
        }
        if ((int) $request->requested_by === $approverId) {
            throw ValidationException::withMessages(['approved_by' => 'The requester cannot approve their own request.']);
        }

        DB::transaction(function () use ($request, $approverId): void {
            $metadata = is_array($request->metadata) ? $request->metadata : [];
            $history = is_array($metadata['status_history'] ?? null) ? $metadata['status_history'] : [];
            $history[] = ['from' => $request->status, 'to' => 'approved', 'actor_id' => $approverId, 'at' => now()->toISOString()];
            $metadata['status_history'] = $history;
            $request->forceFill(['status' => 'approved', 'approved_by' => $approverId, 'metadata' => $metadata])->save();
        });

        return $request->refresh();
    }
}
