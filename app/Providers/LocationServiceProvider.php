<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Services\AddressGeocoder;
use App\Services\Auth\AuthenticatedCase;
use App\Services\GoogleAddressGeocodingService as ServicesLocationService;

class LocationServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(AddressGeocoder::class, ServicesLocationService::class);
    }

    public function provides(): array
    {
        return [
            AddressGeocoder::class,
        ];
    }
}
