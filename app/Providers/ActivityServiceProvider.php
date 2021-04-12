<?php

namespace App\Providers;

use App\Services\Activity\ActivityLogger;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

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
    }

    public function provides()
    {
        return [
            \Spatie\Activitylog\ActivityLogger::class,
        ];
    }
}
