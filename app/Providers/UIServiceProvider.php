<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Services\UIServiceInterface;
use App\Services\UIService;

class UIServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(UIServiceInterface::class, UIService::class);

        $this->app->alias(UIServiceInterface::class, 'ui.service');
    }

    public function provides()
    {
        return [
            UIServiceInterface::class,
            'ui.service',
        ];
    }
}
