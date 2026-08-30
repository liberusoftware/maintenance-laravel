<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Tasks\Api;

use Illuminate\Support\ServiceProvider;

final class TasksApiServiceProvider extends ServiceProvider
{
    public function boot(): void { $this->loadRoutesFrom(__DIR__.'/../routes/api.php'); }
}
