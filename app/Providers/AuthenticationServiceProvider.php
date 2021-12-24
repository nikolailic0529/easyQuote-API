<?php

namespace App\Providers;

use App\Contracts\Services\AuthServiceInterface;
use App\Services\Auth\AuthenticatedCase;
use App\Services\Auth\AuthService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
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
        $this->app->singleton(AuthServiceInterface::class, AuthService::class);

        $this->app->alias(AuthServiceInterface::class, 'auth.service');

        $this->app->alias(ClientRepository::class, 'passport.client.repository');

        $this->app->alias(AuthenticatedCase::class, 'auth.case');
    }

    public function provides(): array
    {
        return [
            AuthServiceInterface::class,
            'auth.service',
            'auth.case',
            'passport.client.repository',
        ];
    }
}
