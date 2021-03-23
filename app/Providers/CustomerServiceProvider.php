<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\Services\CustomerFlow;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Services\CustomerFlow as ServicesCustomerFlow;
use App\Contracts\Services\CustomerState;
use App\Services\CustomerQueries;
use App\Services\CustomerStateProcessor;
use App\Queries\WorldwideCustomerQueries;

class CustomerServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(CustomerQueries::class);

        $this->app->singleton(CustomerFlow::class, ServicesCustomerFlow::class);

        $this->app->singleton(CustomerState::class, CustomerStateProcessor::class);

        $this->app->singleton(WorldwideCustomerQueries::class);
    }

    public function provides()
    {
        return [
            CustomerFlow::class,
            WorldwideCustomerQueries::class,
            CustomerQueries::class,
            CustomerState::class,
        ];
    }
}
