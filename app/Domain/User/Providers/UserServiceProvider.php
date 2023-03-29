<?php

namespace App\Domain\User\Providers;

use App\Domain\User\Contracts\UserForm;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\Middleware\EnforceChangePassword;
use App\Domain\User\Repositories\UserForm as RepositoriesUserForm;
use App\Domain\User\Repositories\UserRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(UserRepositoryInterface::class, UserRepository::class);

        $this->app->singleton(UserForm::class, RepositoriesUserForm::class);

        $this->app->alias(UserRepositoryInterface::class, 'user.repository');

        $this->app->scoped(EnforceChangePassword::class, static function (Container $container): EnforceChangePassword {
            return new EnforceChangePassword(
                config: $container['config']['user.password_expiration'] ?? []
            );
        });
    }
}
