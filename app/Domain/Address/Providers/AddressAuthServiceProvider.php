<?php

namespace App\Domain\Address\Providers;

use App\Domain\Address\Models\Address;
use App\Domain\Address\Policies\AddressPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AddressAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Address::class, AddressPolicy::class);
    }
}
