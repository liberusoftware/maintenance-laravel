<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Procurement\Models\PurchaseRequest;

class CreatePurchaseRequest
{
    public function handle(int $teamId, array $attributes): PurchaseRequest
    {
        $title = trim((string) ($attributes['title'] ?? ''));
        $amount = (float) ($attributes['amount'] ?? 0);
        if ($title === '' || $amount < 0) {
            throw ValidationException::withMessages(['title' => 'A title and non-negative amount are required.']);
        }

        return DB::transaction(fn () => PurchaseRequest::create(array_merge($attributes, ['team_id' => $teamId, 'title' => $title, 'amount' => $amount, 'currency' => $attributes['currency'] ?? 'USD', 'status' => 'pending', 'requested_by' => $attributes['requested_by'] ?? null])));
    }
}
