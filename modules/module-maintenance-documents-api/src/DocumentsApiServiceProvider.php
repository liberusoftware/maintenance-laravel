<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Documents\Api;

use Illuminate\Support\ServiceProvider;

final class DocumentsApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
