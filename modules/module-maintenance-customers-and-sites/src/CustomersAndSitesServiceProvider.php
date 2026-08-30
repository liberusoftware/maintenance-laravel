<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Customer;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Contact;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Location;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\ServiceWindow;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Site;
use Liberu\Modules\Maintenance\CustomersAndSites\Policies\CustomerPolicy;
use Liberu\Modules\Maintenance\CustomersAndSites\Policies\CustomerSiteDetailPolicy;
use Liberu\Modules\Maintenance\CustomersAndSites\Policies\SitePolicy;

class CustomersAndSitesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Site::class, SitePolicy::class);
        Gate::policy(Contact::class, CustomerSiteDetailPolicy::class);
        Gate::policy(Location::class, CustomerSiteDetailPolicy::class);
        Gate::policy(ServiceWindow::class, CustomerSiteDetailPolicy::class);
    }
}
