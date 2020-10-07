<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Repositories\AssetCategoryRepository;
use App\Contracts\Repositories\AssetRepository;
use App\Repositories\AssetCategoryRepository as RepositoriesAssetCategoryRepository;
use App\Repositories\AssetRepository as RepositoriesAssetRepository;

class AssetServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(AssetRepository::class, RepositoriesAssetRepository::class);

        $this->app->singleton(AssetCategoryRepository::class, RepositoriesAssetCategoryRepository::class);
    }

    public function provides()
    {
        return [
            AssetRepository::class,
            AssetCategoryRepository::class,
        ];
    }
}
