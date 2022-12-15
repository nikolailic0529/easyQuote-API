<?php

namespace App\Providers;

use App\Contracts\Services\AddressGeocoder;
use App\Integrations\Google\AddressValidation\AddressValidationIntegration;
use App\Integrations\Google\AddressValidation\CachingAddressValidationIntegration;
use App\Integrations\Google\AddressValidation\ValidatesAddress;
use App\Services\Address\ValidateAddressService;
use App\Services\GoogleAddressGeocodingService as ServicesLocationService;
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
