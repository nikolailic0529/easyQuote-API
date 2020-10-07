<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Services\Stats;
use App\Services\StatsService;

class DashboardServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Stats::class, StatsService::class);

        $this->app->alias(Stats::class, 'stats.service');
    }

    public function provides()
    {
        return [
            Stats::class,
            'stats.service',
        ];
    }
}
