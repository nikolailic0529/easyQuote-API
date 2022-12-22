<?php

namespace App\Providers;

use App\Services\DataAllocation\DataAllocationEntityService;
use App\Services\DataAllocation\DataAllocationFileEntityService;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class DataAllocationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->when([DataAllocationFileEntityService::class, DataAllocationEntityService::class])
            ->needs(Filesystem::class)
            ->give(static function (Container $container): Filesystem {
                return $container['filesystem']->disk('data_allocation_files');
            });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
