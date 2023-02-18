<?php

namespace App\Domain\Build\Providers;

use App\Domain\Build\Contracts\BuildRepositoryInterface;
use App\Domain\Build\Repositories\BuildRepository;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

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
