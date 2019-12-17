<?php

namespace App\Contracts\Repositories\Customer;

use App\Models\Customer\Customer;

interface CustomerRepositoryInterface
{
    /**
     * Get all S4 customers.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all();

    /**
     * Retrieve NonInUse Customers.
     *
     * @return void
     */
    public function drafted();

    /**
     * Forget Cached Drafted Customers.
     *
     * @return bool
     */
    public function forgetDraftedCache(): bool;

    /**
     * Find Customer by id.
     *
     * @param string $id
     * @return \App\Models\Customer\Customer
     */
    public function find(string $id);

    /**
     * Retrieve random Customer.
     *
     * @return \App\Models\Customer\Customer
     */
    public function random(): Customer;

    /**
     * Create a new Customer.
     *
     * @param mixed $attributes
     * @return \App\Models\Customer\Customer|null
     */
    public function create($attributes);
}
