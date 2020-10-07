<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Repositories\CurrencyRepositoryInterface;
use App\Repositories\CurrencyRepository;

class CurrencyServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(CurrencyRepositoryInterface::class, CurrencyRepository::class);

        $this->app->alias(CurrencyRepositoryInterface::class, 'currency.repository');
    }

    public function provides()
    {
        return [
            CurrencyRepositoryInterface::class,
            'currency.repository',
        ];
    }
}
