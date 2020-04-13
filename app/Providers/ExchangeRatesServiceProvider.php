<?php

namespace App\Providers;

use App\Repositories\RateFileRepository;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Foundation\Application;

class ExchangeRatesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // 
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind(RateFileRepository::class, function (Application $app) {
            $diskName = config('exchange-rates.disk');

            $disk = $app->make(Factory::class)->disk($diskName);

            return RateFileRepository::make($disk);
        });

        $this->app->alias(RateFileRepository::class, 'rate-file.repository');
    }
}
