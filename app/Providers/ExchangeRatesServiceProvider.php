<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Repositories\ExchangeRateRepositoryInterface;
use App\Contracts\Services\ManagesExchangeRates;
use App\Repositories\{ExchangeRateRepository, RateFileRepository};
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Foundation\Application;

class ExchangeRatesServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ExchangeRateRepositoryInterface::class, ExchangeRateRepository::class);

        $this->app->singleton(ManagesExchangeRates::class, ER_SERVICE_CLASS);

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
            ExchangeRateRepositoryInterface::class,
            ManagesExchangeRates::class,
            'exchange.service',
        ];
    }
}
