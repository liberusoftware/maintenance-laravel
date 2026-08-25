<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\LaborAndTime\Api;

use Illuminate\Support\ServiceProvider;

class LaborAndTimeApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
