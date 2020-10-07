<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Repositories\CountryRepositoryInterface;
use App\Repositories\CountryRepository;

class CountryServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(CountryRepositoryInterface::class, CountryRepository::class);

        $this->app->alias(CountryRepositoryInterface::class, 'country.repository');
    }

    public function provides()
    {
        return [
            CountryRepositoryInterface::class,
            'country.repository',
        ];
    }
}
