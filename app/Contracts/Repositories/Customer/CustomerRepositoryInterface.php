<?php

namespace App\Contracts\Repositories\Customer;

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
     * Create a new Customer.
     *
     * @param mixed $attributes
     * @return \App\Models\Customer\Customer|null
     */
    public function create($attributes);
}
