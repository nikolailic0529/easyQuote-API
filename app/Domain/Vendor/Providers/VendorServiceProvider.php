<?php

namespace App\Domain\Vendor\Providers;

use App\Domain\Vendor\Contracts\VendorRepositoryInterface;
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
        //
    }

    public function provides(): array
    {
        return [
            VendorRepositoryInterface::class,
            'vendor.repository',
        ];
    }
}
