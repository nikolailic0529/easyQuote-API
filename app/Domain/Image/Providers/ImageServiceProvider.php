<?php

namespace App\Domain\Image\Providers;

use App\Domain\Image\Services\ThumbnailService;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class ImageServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->when(ThumbnailService::class)->needs(Filesystem::class)->give(function (Container $container) {
            return $container['filesystem']->disk('public');
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
    }
}
