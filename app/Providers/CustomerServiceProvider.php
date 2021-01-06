<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\Services\CustomerFlow;
use App\Repositories\Customer\CustomerRepository;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Services\CustomerFlow as ServicesCustomerFlow;
use App\Contracts\Repositories\Customer\CustomerRepositoryInterface;

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
