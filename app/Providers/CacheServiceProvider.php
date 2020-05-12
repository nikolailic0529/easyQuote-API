<?php

namespace App\Providers;

use App\Repositories\CountryRepository;
use App\Services\StatsAggregator;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Cache\Repository;

class CacheServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->when(StatsAggregator::class)->needs(Repository::class)
            ->give(fn ($app) => config('cache.default') === 'redis'
                ? (new CacheManager($app))->driver()->tags(StatsAggregator::SUMMARY_CACHE_TAG)
                : (new CacheManager($app))->driver());

        $this->app->when(CountryRepository::class)->needs(Repository::class)
            ->give(fn ($app) => config('cache.default') === 'redis'
                ? (new CacheManager($app))->driver()->tags(CountryRepository::CACHE_TAG)
                : (new CacheManager($app))->driver());
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
