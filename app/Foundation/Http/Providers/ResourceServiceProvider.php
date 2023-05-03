<?php

namespace App\Foundation\Http\Providers;

use App\Foundation\Http\Services\RequestQueryFilter;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class ResourceServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('request.filter', RequestQueryFilter::class);
    }

    public function provides()
    {
        return [
            'request.filter',
        ];
    }
}
