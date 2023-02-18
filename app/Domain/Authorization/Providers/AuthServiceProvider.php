<?php

namespace App\Domain\Authorization\Providers;

use App\Domain\Authentication\Services\UserTeamGate;
use App\Domain\Authorization\Models\Role;
use App\Domain\Authorization\Policies\RolePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(UserTeamGate::class);
    }

    public function boot(): void
    {
        Gate::policy(Role::class, RolePolicy::class);
    }
}
