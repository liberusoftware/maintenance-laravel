<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Api;

use Illuminate\Support\ServiceProvider;

final class MaintenanceCoreApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
