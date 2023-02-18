<?php

namespace App\Domain\Currency\Providers;

use App\Domain\Currency\Contracts\CurrencyRepositoryInterface;
use App\Domain\Currency\Repositories\CurrencyRepository;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

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
