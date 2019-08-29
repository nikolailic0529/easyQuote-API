<?php

namespace App\Providers;

use Laravel\Passport\Passport;
use Laravel\Passport\Client;
use Laravel\Passport\PersonalAccessClient;
use Webpatser\Uuid\Uuid;
use Illuminate\Support\ServiceProvider;
use Schema;
use App\Http\Controllers\API \ {
    AuthController
};
use App\Contracts \ {
    Services\AuthServiceInterface,
    Repositories\TimezoneRepositoryInterface,
    Repositories\CountryRepositoryInterface,
    Repositories\UserRepositoryInterface,
    Repositories\AccessAttemptRepositoryInterface,
    Repositories\QuoteFileRepositoryInterface,
    Repositories\LanguageRepositoryInterface
};
use App\Repositories \ {
    TimezoneRepository,
    CountryRepository,
    UserRepository,
    AccessAttemptRepository,
    QuoteFileRepository,
    LanguageRepository
};
use App\Services \ {
    AuthService
};

class AppServiceProvider extends ServiceProvider
{
    public $singletons = [
        TimezoneRepositoryInterface::class => TimezoneRepository::class,
        CountryRepositoryInterface::class => CountryRepository::class,
        UserRepositoryInterface::class => UserRepository::class,
        AccessAttemptRepositoryInterface::class => AccessAttemptRepository::class,
        QuoteFileRepositoryInterface::class => QuoteFileRepository::class,
        LanguageRepositoryInterface::class => LanguageRepository::class
    ];
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Passport::ignoreMigrations();

        $this->app->when(AuthController::class)->needs(AuthServiceInterface::class)->give(AuthService::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        Client::creating(function (Client $client) {
            $client->incrementing = false;
            $client->id = Uuid::generate()->string;
        });
        
        Client::retrieved(function (Client $client) {
            $client->incrementing = false;
        });

        PersonalAccessClient::creating(function (PersonalAccessClient $client) {
            $client->incrementing = false;
            $client->id = Uuid::generate()->string;
        });
    }
}
