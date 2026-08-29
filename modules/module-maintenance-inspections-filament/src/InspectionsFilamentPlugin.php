<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Modules\Maintenance\Inspections\Filament\Resources\InspectionResource;

final class InspectionsFilamentPlugin implements Plugin
{
    public function getId(): string
    {
        return 'module-maintenance-inspections';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([InspectionResource::class]);
    }

    public function boot(Panel $panel): void {}
}
