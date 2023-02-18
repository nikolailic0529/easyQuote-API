<?php

namespace App\Domain\Margin\Providers;

use App\Domain\Margin\Models\Margin;
use App\Domain\Margin\Policies\MarginPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class MarginAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Margin::class, MarginPolicy::class);
    }
}
