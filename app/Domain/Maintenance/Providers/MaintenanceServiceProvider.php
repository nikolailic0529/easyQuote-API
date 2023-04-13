<?php

namespace App\Domain\Maintenance\Providers;

use App\Domain\Maintenance\Contracts\ManagesMaintenanceStatus;
use App\Domain\Maintenance\Services\MaintenanceStatusService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class MaintenanceServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ManagesMaintenanceStatus::class, MaintenanceStatusService::class);
    }

    public function provides(): array
    {
        return [
            ManagesMaintenanceStatus::class,
        ];
    }
}
