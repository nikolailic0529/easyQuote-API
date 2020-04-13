<?php

namespace App\Observers;

use App\Events\RfqDeleted;
use App\Models\Customer\Customer;
use App\Contracts\Repositories\Customer\CustomerRepositoryInterface as Customers;

class CustomerObserver
{
    protected Customers $customers;

    public function __construct(Customers $customers)
    {
        $this->customers = $customers;
    }

    /**
     * Handle the customer "created" event.
     *
     * @param  \App\Models\Customer\Customer  $customer
     * @return void
     */
    public function created(Customer $customer)
    {
        $this->customers->flushListingCache();
    }

    /**
     * Handle the customer "deleted" event.
     *
     * @param  \App\Models\Customer\Customer  $customer
     * @return void
     */
    public function deleted(Customer $customer)
    {
        $this->customers->flushListingCache();

        event(new RfqDeleted($customer));
    }
}
