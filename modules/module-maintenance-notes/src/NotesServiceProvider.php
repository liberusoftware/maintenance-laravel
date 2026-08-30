<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Notes;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Modules\Maintenance\Notes\Models\MaintenanceNote;
use Liberu\Modules\Maintenance\Notes\Policies\MaintenanceNotePolicy;

final class NotesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Gate::policy(MaintenanceNote::class, MaintenanceNotePolicy::class);
    }
}
