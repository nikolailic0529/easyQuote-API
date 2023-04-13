<?php

namespace App\Domain\Margin\Providers;

use App\Domain\Margin\Models\CountryMargin;
use App\Domain\Margin\Policies\MarginPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class MarginAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(CountryMargin::class, MarginPolicy::class);
    }
}
