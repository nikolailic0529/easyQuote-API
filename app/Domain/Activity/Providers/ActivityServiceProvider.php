<?php

namespace App\Domain\Activity\Providers;

use App\Domain\Activity\Services\ActivityLogger;
use App\Foundation\Support\Mixins\ActivityLoggerMixin;
use Illuminate\Support\ServiceProvider;

class ActivityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(\Spatie\Activitylog\ActivityLogger::class, ActivityLogger::class);
    }

    public function boot(): void
    {
        \Spatie\Activitylog\ActivityLogger::mixin(new ActivityLoggerMixin());
    }

    public function provides(): array
    {
        return [
            \Spatie\Activitylog\ActivityLogger::class,
        ];
    }
}
