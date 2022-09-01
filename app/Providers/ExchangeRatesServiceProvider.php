<?php

namespace App\Providers;

use App\Contracts\Services\ManagesExchangeRates;
use App\Repositories\RateFileRepository;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class ExchangeRatesServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(ManagesExchangeRates::class, ER_SERVICE_CLASS);

        $this->app->bind(RateFileRepository::class, function (Application $app) {
            $diskName = $this->app['config']->get('exchange-rates.disk');

            $disk = $app->make(Factory::class)->disk($diskName);

            return RateFileRepository::make($disk);
        });

        $this->app->alias(RateFileRepository::class, 'rate-file.repository');

        $this->app->alias(ManagesExchangeRates::class, 'exchange.service');
    }

    public function provides()
    {
        return [
            RateFileRepository::class,
            'rate-file.repository',
            ManagesExchangeRates::class,
            'exchange.service',
        ];
    }
}
