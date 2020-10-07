<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Repositories\VendorRepositoryInterface;
use App\Repositories\VendorRepository;

class VendorServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(VendorRepositoryInterface::class, VendorRepository::class);

        $this->app->alias(VendorRepositoryInterface::class, 'vendor.repository');
    }

    public function provides()
    {
        return [
            VendorRepositoryInterface::class,
            'vendor.repository',
        ];
    }
}
