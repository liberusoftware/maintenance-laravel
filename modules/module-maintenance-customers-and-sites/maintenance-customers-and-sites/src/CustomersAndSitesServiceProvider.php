<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Customer;
use Liberu\Modules\Maintenance\CustomersAndSites\Policies\CustomerPolicy;

class CustomersAndSitesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Gate::policy(Customer::class, CustomerPolicy::class);
    }
}
