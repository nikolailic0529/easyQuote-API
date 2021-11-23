<?php

namespace App\Providers;

use App\Contracts\LoggerAware;
use App\Services\SalesOrder\RefreshSalesOrderStatusService;
use Illuminate\Support\ServiceProvider;

class SalesOrderServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->afterResolving(RefreshSalesOrderStatusService::class, function (RefreshSalesOrderStatusService $concrete) {
            if ($concrete instanceof LoggerAware) {
                $concrete->setLogger($this->app['log']->channel('sales-orders'));
            }
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
