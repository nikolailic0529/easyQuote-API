<?php

namespace App\Domain\Rescue\Providers;

use App\Domain\Rescue\Contracts\CustomerState;
use App\Domain\Rescue\Contracts\MigratesCustomerEntity;
use App\Domain\Rescue\Models\Customer;
use App\Domain\Rescue\Observers\CustomerObserver;
use App\Domain\Rescue\Queries\CustomerQueries;
use App\Domain\Rescue\Services\CustomerFlowService as ServicesCustomerFlow;
use App\Domain\Rescue\Services\CustomerStateProcessor;
use Illuminate\Support\ServiceProvider;

class CustomerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CustomerQueries::class);

        $this->app->singleton(MigratesCustomerEntity::class, ServicesCustomerFlow::class);

        $this->app->singleton(CustomerState::class, CustomerStateProcessor::class);
    }

    public function boot(): void
    {
        Customer::observe(CustomerObserver::class);
    }

    public function provides(): array
    {
        return [
            MigratesCustomerEntity::class,
            CustomerQueries::class,
            CustomerState::class,
        ];
    }
}
