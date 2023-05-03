<?php

namespace App\Foundation\Mail\Providers;

use App\Foundation\Mail\Services\MailRateLimiter;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

class MailServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->when(MailRateLimiter::class)
            ->needs(RateLimiter::class)
            ->give(static function (Container $container): RateLimiter {
                return new RateLimiter(
                    $container->make('cache')->driver(
                        $container['config']->get('mail.limiter.driver'),
                    )
                );
            });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
    }
}
