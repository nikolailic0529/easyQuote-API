<?php

namespace App\Domain\Rescue\Observers;

use App\Domain\Rescue\Events\Customer\RfqDeleted;
use App\Domain\Rescue\Models\Customer;

class CustomerObserver
{
    /**
     * Handle the customer "created" event.
     *
     * @return void
     */
    public function created(Customer $customer)
    {
    }

    /**
     * Handle the customer "deleted" event.
     *
     * @return void
     */
    public function deleted(Customer $customer)
    {
        event(new RfqDeleted($customer));
    }
}
