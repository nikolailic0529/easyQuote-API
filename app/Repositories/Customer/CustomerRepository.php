<?php namespace App\Repositories\Customer;

use App\Contracts\Repositories\Customer\CustomerRepositoryInterface;
use App\Models\Customer\Customer;

class CustomerRepository implements CustomerRepositoryInterface
{
    protected $customer;

    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    public function all()
    {
        return $this->customer->all();
    }
}
