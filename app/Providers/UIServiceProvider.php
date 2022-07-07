<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Services\RouteResolver;
use App\Services\UiRouteResolver;

class UIServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(RouteResolver::class, UiRouteResolver::class);

        $this->app->alias(RouteResolver::class, 'ui.route');
    }

    public function provides()
    {
        return [
            RouteResolver::class,
            'ui.route',
        ];
    }
}
