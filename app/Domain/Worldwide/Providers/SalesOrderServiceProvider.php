<?php

namespace App\Domain\Worldwide\Providers;

use App\Domain\Worldwide\Contracts\ProcessesSalesOrderState;
use App\Domain\Worldwide\Services\SalesOrder\RefreshSalesOrderStatusService;
use App\Domain\Worldwide\Services\SalesOrder\SalesOrderStateProcessor;
use App\Foundation\Log\Contracts\LoggerAware;
use Illuminate\Support\ServiceProvider;

class SalesOrderServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->afterResolving(RefreshSalesOrderStatusService::class, function (RefreshSalesOrderStatusService $concrete) {
            if ($concrete instanceof LoggerAware) {
                $concrete->setLogger($this->app['log']->channel('sales-orders'));
            }
        });

        $this->app->singleton(ProcessesSalesOrderState::class, SalesOrderStateProcessor::class);
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
