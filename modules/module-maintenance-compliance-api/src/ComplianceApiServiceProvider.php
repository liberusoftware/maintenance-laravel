<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Compliance\Api;

use Illuminate\Support\ServiceProvider;

class ComplianceApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
