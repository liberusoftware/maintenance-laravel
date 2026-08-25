<?php
declare(strict_types=1);
namespace Liberu\Modules\Maintenance\CustomersAndSites\Filament;
use FilamentContracts\Plugin; use Filament\Panel; use Liberu\Modules\Maintenance\CustomersAndSites\Filament\Resources\CustomerResource;
class CustomersAndSitesFilamentPlugin implements Plugin { public function getId(): string{return 'maintenance-customers-and-sites';} public function register(Panel $panel): void{$panel->resources([CustomerResource::class]);} public function boot(Panel $panel): void{} }
