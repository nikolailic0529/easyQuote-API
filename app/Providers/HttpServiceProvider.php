<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Services\HttpInterface;
use App\Services\HttpService;

class HttpServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(HttpInterface::class, HttpService::class);

        $this->app->alias(HttpInterface::class, 'http.service');
    }

    public function provides()
    {
        return [
            HttpInterface::class,
            'http.service',
        ];
    }
}
