<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Modules\Maintenance\Inspections\Models\Inspection;
use Liberu\Modules\Maintenance\Inspections\Models\InspectionTemplate;
use Liberu\Modules\Maintenance\Inspections\Policies\InspectionPolicy;
use Liberu\Modules\Maintenance\Inspections\Policies\InspectionTemplatePolicy;

class InspectionsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Gate::policy(Inspection::class, InspectionPolicy::class);
        Gate::policy(InspectionTemplate::class, InspectionTemplatePolicy::class);
    }
}
