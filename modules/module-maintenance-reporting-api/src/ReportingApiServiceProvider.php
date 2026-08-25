<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Reporting\Api;

use Illuminate\Support\ServiceProvider;

class ReportingApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
