<?php

namespace App\Domain\Country\Providers;

use App\Domain\Country\Models\Country;
use App\Domain\Country\Policies\CountryPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class CountryAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Country::class, CountryPolicy::class);
    }
}
