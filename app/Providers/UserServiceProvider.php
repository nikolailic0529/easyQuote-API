<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Repositories\UserForm;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Repositories\UserForm as RepositoriesUserForm;
use App\Repositories\UserRepository;

class UserServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(UserRepositoryInterface::class, UserRepository::class);

        $this->app->singleton(UserForm::class, RepositoriesUserForm::class);

        $this->app->alias(UserRepositoryInterface::class, 'user.repository');
    }

    public function provides()
    {
        return [
            UserRepositoryInterface::class,
            UserForm::class,
            'user.repository',
        ];
    }
}
