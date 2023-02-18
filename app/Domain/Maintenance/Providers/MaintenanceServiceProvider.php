<?php

namespace App\Domain\Maintenance\Providers;

use App\Domain\Maintenance\Contracts\MaintenanceServiceInterface;
use App\Domain\Maintenance\Services\MaintenanceService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class MaintenanceServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(MaintenanceServiceInterface::class, MaintenanceService::class);
    }

    public function provides(): array
    {
        return [
            MaintenanceServiceInterface::class,
        ];
    }
}
