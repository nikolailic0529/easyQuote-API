<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Repositories\AccessAttemptRepositoryInterface;
use App\Repositories\AccessAttemptRepository;

class AccessServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(AccessAttemptRepositoryInterface::class, AccessAttemptRepository::class);
    }

    public function provides()
    {
        return [
            AccessAttemptRepositoryInterface::class
        ];
    }
}
