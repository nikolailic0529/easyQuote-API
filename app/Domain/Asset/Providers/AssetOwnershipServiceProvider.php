<?php

namespace App\Domain\Asset\Providers;

use App\Domain\Asset\Services\AssetOwnershipService;
use App\Domain\Shared\Ownership\Contracts\ChangeOwnershipStrategy;
use Illuminate\Support\ServiceProvider;

class AssetOwnershipServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag(AssetOwnershipService::class, ChangeOwnershipStrategy::class);
    }
}