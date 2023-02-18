<?php

namespace App\Domain\Country\Providers;

use App\Domain\Country\Contracts\CountryRepositoryInterface;
use App\Domain\Country\Repositories\CountryRepository;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

class CountryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->when(CountryRepository::class)
            ->needs(Repository::class)
            ->give(static function (Container $app): mixed {
                $store = $app['cache']->driver();

                return $store->supportsTags() ? $store->tags(CountryRepository::CACHE_TAG) : $store;
            });

        $this->app->singleton(CountryRepositoryInterface::class, CountryRepository::class);

        $this->app->alias(CountryRepositoryInterface::class, 'country.repository');
    }

    public function provides(): array
    {
        return [
            CountryRepositoryInterface::class,
            'country.repository',
        ];
    }
}
