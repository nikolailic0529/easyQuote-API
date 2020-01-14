<?php

namespace App\Observers;

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
        app('customer.repository')->forgetDraftedCache();
    }

    /**
     * Handle the customer "deleted" event.
     *
     * @param  \App\Models\Customer\Customer  $customer
     * @return void
     */
    public function deleted(Customer $customer)
    {
        app('customer.repository')->forgetDraftedCache();
    }

    /**
     * Handle the customer "submitted" event.
     *
     * @param Customer $customer
     * @return void
     */
    public function submitted(Customer $customer)
    {
        app('customer.repository')->forgetDraftedCache();
    }

    /**
     * Handle the customer "unsubmitted" event.
     *
     * @param Customer $customer
     * @return void
     */
    public function unsubmitted(Customer $customer)
    {
        app('customer.repository')->forgetDraftedCache();
    }
}
