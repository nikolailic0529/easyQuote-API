<?php

namespace App\Providers;

use App\Contracts\Repositories\AssetCategoryRepository;
use App\Contracts\Services\MigratesAssetEntity;
use App\Repositories\AssetCategoryRepository as RepositoriesAssetCategoryRepository;
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
        $this->app->singleton(AssetCategoryRepository::class, RepositoriesAssetCategoryRepository::class);

        $this->app->singleton(MigratesAssetEntity::class, AssetFlowService::class);
    }

    public function provides()
    {
        return [
            AssetCategoryRepository::class,
            MigratesAssetEntity::class,
        ];
    }
}
