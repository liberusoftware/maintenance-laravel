<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Procurement\Models\PurchaseRequest;

final class TransitionPurchaseRequest
{
    /** @var array<string, array<int, string>> */
    private const ALLOWED = [
        'pending' => ['cancelled'],
        'approved' => ['ordered', 'cancelled'],
        'ordered' => ['received', 'cancelled'],
        'rejected' => [],
        'received' => [],
        'cancelled' => [],
    ];

    public function handle(int $teamId, PurchaseRequest $request, string $status, ?int $actorId = null): PurchaseRequest
    {
        abort_unless((int) $request->team_id === $teamId, 404);
        if (! in_array($status, self::ALLOWED[$request->status] ?? [], true)) {
            throw ValidationException::withMessages(['status' => 'That purchase-request transition is not allowed.']);
        }

        return DB::transaction(function () use ($request, $status, $actorId): PurchaseRequest {
            $metadata = is_array($request->metadata) ? $request->metadata : [];
            $history = is_array($metadata['status_history'] ?? null) ? $metadata['status_history'] : [];
            $history[] = ['from' => $request->status, 'to' => $status, 'actor_id' => $actorId, 'at' => now()->toISOString()];
            $metadata['status_history'] = $history;
            $request->forceFill(['status' => $status, 'metadata' => $metadata])->save();

            return $request->refresh();
        });
    }
}
