<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Api;

use Illuminate\Support\ServiceProvider;

class AssetsApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
