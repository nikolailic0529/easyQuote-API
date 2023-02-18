<?php

namespace App\Domain\SalesUnit\Providers;

use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\SalesUnit\Policies\SalesUnitPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class SalesUnitAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(SalesUnit::class, SalesUnitPolicy::class);
    }
}
