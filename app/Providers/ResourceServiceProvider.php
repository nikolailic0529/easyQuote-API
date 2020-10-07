<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Http\Resources\RequestQueryFilter;

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
            'request.filter'
        ];
    }
}
