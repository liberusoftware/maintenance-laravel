<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Notes\Api;

use Illuminate\Support\ServiceProvider;

final class NotesApiServiceProvider extends ServiceProvider
{
    public function boot(): void { $this->loadRoutesFrom(__DIR__.'/../routes/api.php'); }
}
