<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\LaborAndTime\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\LaborAndTime\Models\Expense;

final class CreateExpense
{
    public function handle(int $teamId, array $attributes): Expense
    {
        if (trim((string) ($attributes['description'] ?? '')) === '' || ! isset($attributes['amount']) || (float) $attributes['amount'] < 0) {
            throw ValidationException::withMessages(['description' => 'A description and non-negative amount are required.']);
        }

        return DB::transaction(fn (): Expense => Expense::create(array_merge($attributes, ['team_id' => $teamId, 'description' => trim((string) $attributes['description']), 'currency' => strtoupper((string) ($attributes['currency'] ?? 'USD')), 'status' => $attributes['status'] ?? 'pending'])));
    }
}
