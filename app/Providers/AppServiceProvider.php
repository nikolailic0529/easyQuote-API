<?php

namespace App\Providers;

use Laravel\Passport\Passport;
use Laravel\Passport\Client;
use Laravel\Passport\PersonalAccessClient;
use Webpatser\Uuid\Uuid;
use Illuminate\Support\ServiceProvider;
use Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Passport::ignoreMigrations();
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
