<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Repositories\System\ClientCredentialsInterface;
use App\Contracts\Services\AuthServiceInterface;
use App\Repositories\System\ClientCredentialsRepository;
use App\Services\Auth\AuthService;
use Laravel\Passport\ClientRepository;

class AuthenticationServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ClientCredentialsInterface::class, ClientCredentialsRepository::class);

        $this->app->singleton(AuthServiceInterface::class, AuthService::class);

        $this->app->alias(AuthServiceInterface::class, 'auth.service');

        $this->app->alias(ClientRepository::class, 'passport.client.repository');

        $this->app->alias(ClientCredentialsInterface::class, 'client.repository');
    }

    public function provides()
    {
        return [
            AuthServiceInterface::class,
            'auth.service',
            ClientCredentialsInterface::class,
            'client.repository',
            'passport.client.repository',
        ];
    }
}
