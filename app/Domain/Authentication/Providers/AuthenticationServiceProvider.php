<?php

namespace App\Domain\Authentication\Providers;

use App\Domain\Authentication\Contracts\AuthServiceInterface;
use App\Domain\Authentication\Services\AuthenticatedCase;
use App\Domain\Authentication\Services\AuthService;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\PersonalAccessClient;
use Webpatser\Uuid\Uuid;

class AuthenticationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuthServiceInterface::class, AuthService::class);

        $this->app->alias(AuthServiceInterface::class, 'auth.service');

        $this->app->alias(ClientRepository::class, 'passport.client.repository');

        $this->app->alias(AuthenticatedCase::class, 'auth.case');
    }

    public function boot(): void
    {
        Passport::ignoreMigrations();

        Client::creating(function (Client $client) {
            $client->setIncrementing(false);
            $client->{$client->getKeyName()} = Uuid::generate(4)->string;
        });

        Client::retrieved(function (Client $client) {
            $client->setIncrementing(false);
        });

        PersonalAccessClient::creating(function (PersonalAccessClient $client) {
            $client->setIncrementing(false);
            $client->{$client->getKeyName()} = Uuid::generate(4)->string;
        });

        Passport::routes();
        Passport::personalAccessTokensExpireIn(now()->addMinutes(config('auth.tokens.expire')));
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
