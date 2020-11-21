<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Services\LocationService;
use App\Services\Auth\AuthenticatedCase;
use App\Services\LocationService as ServicesLocationService;

class LocationServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(LocationService::class, ServicesLocationService::class);

        $this->app->alias(AuthenticatedCase::class, 'auth.case');
    }

    public function provides()
    {
        return [
            LocationService::class,
            'auth.case',
        ];
    }
}
