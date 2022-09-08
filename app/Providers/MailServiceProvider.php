<?php

namespace App\Providers;

use App\Services\Mail\MailRateLimiter;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

class MailServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
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
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }
}
