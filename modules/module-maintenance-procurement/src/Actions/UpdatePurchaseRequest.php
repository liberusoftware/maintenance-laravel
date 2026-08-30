<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Procurement\Models\PurchaseRequest;

final class UpdatePurchaseRequest
{
    /** @param array<string, mixed> $attributes */
    public function handle(int $teamId, PurchaseRequest $request, array $attributes): PurchaseRequest
    {
        abort_unless((int) $request->team_id === $teamId, 404);
        if (array_key_exists('status', $attributes) && $attributes['status'] !== $request->status) {
            throw ValidationException::withMessages(['status' => 'Use the approval action to change request status.']);
        }
        $title = array_key_exists('title', $attributes) ? trim((string) $attributes['title']) : $request->title;
        $amount = array_key_exists('amount', $attributes) ? (float) $attributes['amount'] : (float) $request->amount;
        if ($title === '' || $amount < 0) {
            throw ValidationException::withMessages(['title' => 'A title and non-negative amount are required.']);
        }

        return DB::transaction(function () use ($request, $attributes, $title, $amount): PurchaseRequest {
            $request->fill(array_merge($attributes, ['title' => $title, 'amount' => $amount]));
            $request->save();

            return $request->refresh();
        });
    }
}
