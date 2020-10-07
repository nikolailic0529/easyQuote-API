<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Factories\FailureInterface;
use App\Factories\Failure\Failure;

class FailureServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(FailureInterface::class, Failure::class);
    }

    public function provides()
    {
        return [
            FailureInterface::class,
        ];
    }
}
