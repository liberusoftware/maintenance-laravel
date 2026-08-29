<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Customer;

final class DeleteCustomer
{
    public function handle(int $teamId, Customer $customer): void
    {
        abort_unless((int) $customer->team_id === $teamId, 404);
        DB::transaction(static fn (): bool => (bool) $customer->delete());
    }
}
