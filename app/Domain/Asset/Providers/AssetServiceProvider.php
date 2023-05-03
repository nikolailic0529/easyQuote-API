<?php

namespace App\Domain\Asset\Providers;

use App\Domain\Asset\Contracts\MigratesAssetEntity;
use App\Domain\Asset\Services\AssetFlowService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class AssetServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton(MigratesAssetEntity::class, AssetFlowService::class);
    }

    public function provides(): array
    {
        return [
            MigratesAssetEntity::class,
        ];
    }
}
