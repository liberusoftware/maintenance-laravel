<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Documents;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Modules\Maintenance\Documents\Models\MaintenanceDocument;
use Liberu\Modules\Maintenance\Documents\Policies\MaintenanceDocumentPolicy;

final class DocumentsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Gate::policy(MaintenanceDocument::class, MaintenanceDocumentPolicy::class);
    }
}
