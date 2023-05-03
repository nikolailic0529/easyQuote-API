<?php

namespace App\Domain\Asset\Providers;

use App\Domain\Asset\Models\Asset;
use App\Domain\Asset\Policies\AssetPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AssetAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Asset::class, AssetPolicy::class);
    }
}
