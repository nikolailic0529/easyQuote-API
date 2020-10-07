<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Repositories\System\ActivityRepositoryInterface;
use App\Repositories\System\ActivityRepository;
use App\Services\ActivityLogger;

class ActivityServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(\Spatie\Activitylog\ActivityLogger::class, ActivityLogger::class);

        $this->app->singleton(ActivityRepositoryInterface::class, ActivityRepository::class);
    }

    public function provides()
    {
        return [
            ActivityRepositoryInterface::class,
            \Spatie\Activitylog\ActivityLogger::class,
        ];
    }
}
