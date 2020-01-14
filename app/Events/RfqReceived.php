<?php

namespace App\Events;

use App\Models\Customer\Customer;
use Illuminate\Foundation\Events\Dispatchable;

class RfqReceived
{
    use Dispatchable;

    /** @var \App\Models\Customer\Customer */
    public $customer;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }
}
