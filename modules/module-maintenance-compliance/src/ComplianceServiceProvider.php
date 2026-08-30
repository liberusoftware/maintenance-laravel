<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Compliance;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Modules\Maintenance\Compliance\Models\ComplianceIncident;
use Liberu\Modules\Maintenance\Compliance\Models\CompliancePermit;
use Liberu\Modules\Maintenance\Compliance\Models\ComplianceRequirement;
use Liberu\Modules\Maintenance\Compliance\Models\ComplianceRiskAssessment;
use Liberu\Modules\Maintenance\Compliance\Policies\ComplianceCapabilityPolicy;

class ComplianceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        foreach ([ComplianceIncident::class, CompliancePermit::class, ComplianceRequirement::class, ComplianceRiskAssessment::class] as $model) {
            Gate::policy($model, ComplianceCapabilityPolicy::class);
        }
    }
}
