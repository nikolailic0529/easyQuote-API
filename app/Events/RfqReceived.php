<?php

namespace App\Events;

use App\Models\Customer\Customer;
use Illuminate\Foundation\Events\Dispatchable;

class RfqReceived
{
    use Dispatchable;

    /** @var \App\Models\Customer\Customer */
    public $customer;

    /** @var string */
    public $service;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Customer $customer, string $service)
    {
        $this->customer = $customer;
        $this->service = $service;
    }
}
