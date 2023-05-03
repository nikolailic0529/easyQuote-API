<?php

namespace App\Domain\Authorization\Providers;

use App\Domain\Authorization\Contracts\ModuleRepository;
use App\Domain\Authorization\Contracts\RolePropertyRepository;
use App\Domain\Authorization\Repositories\ConfigAwareModuleRepository;
use App\Domain\Authorization\Repositories\ConfigAwareRolePropertyRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ModuleRepository::class, static function (Container $container): ModuleRepository {
            return new ConfigAwareModuleRepository($container['config']['role']);
        });

        $this->app->bind(RolePropertyRepository::class, static function (Container $container): RolePropertyRepository {
            return new ConfigAwareRolePropertyRepository($container['config']['role']);
        });
    }
}
