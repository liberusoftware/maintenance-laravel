<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrder;
use Liberu\Modules\Maintenance\WorkOrders\Policies\WorkOrderPolicy;

class WorkOrdersServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Gate::policy(WorkOrder::class, WorkOrderPolicy::class);
    }
}
