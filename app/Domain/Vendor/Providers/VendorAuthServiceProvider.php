<?php

namespace App\Domain\Vendor\Providers;

use App\Domain\Vendor\Models\Vendor;
use App\Domain\Vendor\Policies\VendorPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class VendorAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Vendor::class, VendorPolicy::class);
    }
}
