<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Repositories\System\BuildRepositoryInterface;
use App\Repositories\System\BuildRepository;

class BuildServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(BuildRepositoryInterface::class, BuildRepository::class);

        $this->app->alias(BuildRepositoryInterface::class, 'build.repository');
    }

    public function provides()
    {
        return [
            BuildRepositoryInterface::class,
            'build.repository',
        ];
    }
}
