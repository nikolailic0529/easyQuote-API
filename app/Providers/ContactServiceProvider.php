<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Repositories\AddressRepositoryInterface;
use App\Contracts\Repositories\ContactRepositoryInterface;
use App\Repositories\AddressRepository;
use App\Repositories\ContactRepository;

class ContactServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(AddressRepositoryInterface::class, AddressRepository::class);

        $this->app->singleton(ContactRepositoryInterface::class, ContactRepository::class);
    }

    public function provides()
    {
        return [
            AddressRepositoryInterface::class,
            ContactRepositoryInterface::class,
        ];
    }
}
