<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Repositories\RoleRepositoryInterface;
use App\Contracts\Services\PermissionBroker;
use App\Repositories\RoleRepository;
use App\Services\DefaultPermissionBroker as ServicesPermissionBroker;

class PermissionServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(RoleRepositoryInterface::class, RoleRepository::class);

        $this->app->singleton(PermissionBroker::class, ServicesPermissionBroker::class);

        $this->app->alias(RoleRepositoryInterface::class, 'role.repository');
    }

    public function provides()
    {
        return [
            RoleRepositoryInterface::class,
            'role.repository',
            PermissionBroker::class,
        ];
    }
}
