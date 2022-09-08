<?php

namespace App\Providers;

use App\Repositories\CountryRepository;
use Illuminate\Cache\CacheManager;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\ServiceProvider;

class CacheServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->when(CountryRepository::class)->needs(Repository::class)->give(function ($app) {
            return $app['config']['cache.default'] === 'redis'
                ? (new CacheManager($app))->driver()->tags(CountryRepository::CACHE_TAG)
                : (new CacheManager($app))->driver();
        });

        $this->app->singleton(LockProvider::class, function () {
            return $this->app['cache']->driver()->getStore();
        });

        $this->app->singleton('rate-limiter.persistent', function ($app) {
            return new RateLimiter($app->make('cache')->driver(
                $app['config']->get('database')
            ));
        });

    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
