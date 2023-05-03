<?php

namespace App\Domain\Location\Providers;

use App\Domain\Address\Services\ValidateAddressService;
use App\Domain\Geocoding\Contracts\AddressGeocoder;
use App\Domain\Geocoding\Integrations\AddressValidation\AddressValidationIntegration;
use App\Domain\Geocoding\Integrations\AddressValidation\CachingAddressValidationIntegration;
use App\Domain\Geocoding\Integrations\AddressValidation\ValidatesAddress;
use App\Domain\Geocoding\Services\GoogleAddressGeocodingService as ServicesLocationService;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

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

        $this->app->when(AddressValidationIntegration::class)
            ->needs('$config')
            ->giveConfig('address-validation');

        $this->app->afterResolving(AddressValidationIntegration::class,
            static function (AddressValidationIntegration $concrete, Container $container): void {
                $concrete->setLogger($container->make('log')->channel('google-requests'));
            });

        $this->app->when(ValidateAddressService::class)
            ->needs(ValidatesAddress::class)
            ->give(CachingAddressValidationIntegration::class);
    }

    public function provides(): array
    {
        return [
            AddressGeocoder::class,
        ];
    }
}
