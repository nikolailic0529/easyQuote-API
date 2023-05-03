<?php

namespace App\Domain\Authorization\Providers;

use App\Domain\Authentication\Services\AuthService;
use App\Domain\Authentication\Services\UserTeamGate;
use App\Domain\Authorization\Models\Role;
use App\Domain\Authorization\Policies\RolePolicy;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(UserTeamGate::class);
        $this->app->when(AuthService::class)
            ->needs(UserProvider::class)
            ->give(static function (Container $container): UserProvider {
                $config = $container['config']['auth.providers.users'];

                return new EloquentUserProvider($container[Hasher::class], $config['model']);
            });
    }

    public function boot(): void
    {
        Gate::policy(Role::class, RolePolicy::class);
    }
}
