<?php

namespace App\Foundation\Cache\Providers;

use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

class CacheServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(LockProvider::class, static function (Container $container): Store {
            return $container['cache.store']->getStore();
        });

        $this->app->singleton('rate-limiter.persistent', static function (Container $container): RateLimiter {
            return new RateLimiter($container->make('cache')->driver(
                $container['config']->get('database')
            ));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
    }
}
