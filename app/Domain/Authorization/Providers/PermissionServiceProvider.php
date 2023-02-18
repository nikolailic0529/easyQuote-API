<?php

namespace App\Domain\Authorization\Providers;

use App\Domain\Authorization\Contracts\PermissionBroker;
use App\Domain\Authorization\Contracts\RoleRepositoryInterface;
use App\Domain\Authorization\Repositories\RoleRepository;
use App\Domain\Authorization\Services\DefaultPermissionBroker as ServicesPermissionBroker;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

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
