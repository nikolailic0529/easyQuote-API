<?php

namespace App\Events;

use App\Http\Resources\CustomerResponseResource;
use Illuminate\Foundation\Events\Dispatchable;

class RfqReceived
{
    use Dispatchable;

    /** @var \App\Http\Resources\CustomerResponseResource */
    public $customer;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(CustomerResponseResource $customer)
    {
        $this->customer = $customer;
    }
}
