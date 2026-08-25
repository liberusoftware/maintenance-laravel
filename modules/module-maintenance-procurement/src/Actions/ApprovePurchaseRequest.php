<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Procurement\Models\PurchaseRequest;

class ApprovePurchaseRequest
{
    public function handle(int $teamId, PurchaseRequest $request, int $approverId): PurchaseRequest
    {
        if ((int) $request->team_id !== $teamId) {
            abort(404);
        }if ($request->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Only pending requests can be approved.']);
        }if ((int) $request->requested_by === $approverId) {
            throw ValidationException::withMessages(['approved_by' => 'The requester cannot approve their own request.']);
        }$request->status = 'approved';
        $request->approved_by = $approverId;
        $request->save();

        return $request->refresh();
    }
}
