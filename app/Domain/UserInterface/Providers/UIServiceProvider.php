<?php

namespace App\Domain\UserInterface\Providers;

use App\Domain\UserInterface\Contracts\RouteResolver;
use App\Domain\UserInterface\Services\UiRouteResolver;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

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
