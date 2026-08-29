<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Modules\Maintenance\Procurement\Models\PurchaseRequest;
use Liberu\Modules\Maintenance\Procurement\Policies\PurchaseRequestPolicy;

class ProcurementServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Gate::policy(PurchaseRequest::class, PurchaseRequestPolicy::class);
    }
}
