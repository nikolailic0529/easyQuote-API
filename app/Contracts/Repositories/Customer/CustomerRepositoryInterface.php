<?php namespace App\Contracts\Repositories\Customer;

interface CustomerRepositoryInterface
{
    /**
     * Get all S4 customers.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all();

    /**
     * Find Customer by id.
     *
     * @param string $id
     * @return \App\Models\Customer\Customer
     */
    public function find(string $id);

    /**
     * Create a new Customer.
     *
     * @param mixed $attributes
     * @return \App\Models\Customer\Customer|null
     */
    public function create($attributes);
}
