<?php

namespace App\Domain\Vendor\Providers;

use App\Domain\Vendor\Contracts\VendorRepositoryInterface;
use App\Domain\Vendor\Models\Vendor;
use App\Domain\Vendor\Observers\VendorObserver;
use App\Domain\Vendor\Repositories\VendorRepository;
use Illuminate\Support\ServiceProvider;

class VendorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(VendorRepositoryInterface::class, VendorRepository::class);

        $this->app->alias(VendorRepositoryInterface::class, 'vendor.repository');
    }

    public function boot(): void
    {
        Vendor::observe(VendorObserver::class);
    }

    public function provides()
    {
        return [
            VendorRepositoryInterface::class,
            'vendor.repository',
        ];
    }
}
