<?php

namespace App\Foundation\Http\Providers;

use App\Foundation\Http\Contracts\HttpInterface;
use App\Foundation\Http\Services\HttpService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

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
