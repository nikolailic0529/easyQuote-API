<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Repositories\Customer\CustomerRepositoryInterface;
use App\Contracts\Services\CustomerFlow;
use App\Repositories\Customer\CustomerRepository;
use App\Services\CustomerFlow as ServicesCustomerFlow;

class CustomerServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(CustomerRepositoryInterface::class, CustomerRepository::class);

        $this->app->singleton(CustomerFlow::class, ServicesCustomerFlow::class);

        $this->app->alias(CustomerRepositoryInterface::class, 'customer.repository');
    }

    public function provides()
    {
        return [
            CustomerRepositoryInterface::class,
            CustomerFlow::class,
            'customer.repository',
        ];
    }
}
