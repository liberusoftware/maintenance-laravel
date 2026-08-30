<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Modules\Maintenance\Assets\Models\Asset;
use Liberu\Modules\Maintenance\Assets\Models\AssetMeter;
use Liberu\Modules\Maintenance\Assets\Models\AssetMeterReading;
use Liberu\Modules\Maintenance\Assets\Policies\AssetPolicy;

class AssetsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Gate::policy(Asset::class, AssetPolicy::class);
        Gate::policy(AssetMeter::class, AssetPolicy::class);
        Gate::policy(AssetMeterReading::class, AssetPolicy::class);
    }
}
