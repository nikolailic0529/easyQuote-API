<?php

namespace App\Observers;

use App\Events\RfqDeleted;
use App\Models\Customer\Customer;

class CustomerObserver
{
    /**
     * Handle the customer "created" event.
     *
     * @param  \App\Models\Customer\Customer  $customer
     * @return void
     */
    public function created(Customer $customer)
    {
        // 
    }

    /**
     * Handle the customer "deleted" event.
     *
     * @param  \App\Models\Customer\Customer  $customer
     * @return void
     */
    public function deleted(Customer $customer)
    {
        event(new RfqDeleted($customer));
    }
}
