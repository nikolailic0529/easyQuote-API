<?php

namespace App\Providers;

use App\Contracts\Services\MigratesAssetEntity;
use App\Services\AssetFlowService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class AssetServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(MigratesAssetEntity::class, AssetFlowService::class);
    }

    public function provides()
    {
        return [
            MigratesAssetEntity::class,
        ];
    }
}
