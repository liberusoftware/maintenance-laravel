<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Modules\Maintenance\Core\Filament\Resources\OrganizationResource;

final class MaintenanceCoreFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'maintenance-core';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([OrganizationResource::class]);
    }

    public function boot(Panel $panel): void {}
}
