<?php

namespace App\Providers;

use App\Contracts\Services\Stats;
use App\Services\Stats\StatsCalculationService;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class DashboardServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Stats::class, function (Container $container) {

            return $container->make(StatsCalculationService::class, [
                'logger' => $container['log']->channel('stats-calculation'),
            ]);

        });

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
