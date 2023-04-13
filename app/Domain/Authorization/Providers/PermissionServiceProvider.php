<?php

namespace App\Domain\Authorization\Providers;

use App\Domain\Authorization\Contracts\PermissionBroker;
use App\Domain\Authorization\Services\DefaultPermissionBroker as ServicesPermissionBroker;
use Illuminate\Support\ServiceProvider;

class PermissionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PermissionBroker::class, ServicesPermissionBroker::class);
    }
}
