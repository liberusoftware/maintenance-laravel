<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Commercial\Api;

use Illuminate\Support\ServiceProvider;

class CommercialApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
