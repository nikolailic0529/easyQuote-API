<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Services\MaintenanceServiceInterface;
use App\Services\MaintenanceService;

class MaintenanceServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(MaintenanceServiceInterface::class, MaintenanceService::class);
    }

    public function provides()
    {
        return [
            MaintenanceServiceInterface::class,
        ];
    }
}
