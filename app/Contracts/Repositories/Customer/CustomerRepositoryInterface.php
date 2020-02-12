<?php

namespace App\Contracts\Repositories\Customer;

use App\Models\Customer\Customer;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

interface CustomerRepositoryInterface
{
    /**
     * Get all S4 customers.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all();

    /**
     * Retrieve Customers without Quotes.
     *
     * @return void
     */
    public function list();

    /**
     * Flush Customers listing cache.
     *
     * @return void
     */
    public function flushListingCache(): void;

    /**
     * Find Customer by id.
     *
     * @param string $id
     * @return \App\Models\Customer\Customer
     */
    public function find(string $id);

    /**
     * Find Customer by RFQ Number.
     *
     * @param string $rfq
     * @return \App\Models\Customer\Customer
     */
    public function findByRfq(string $rfq): Customer;

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

    /**
     * Delete a specified Customer.
     *
     * @param \App\Models\Customer\Customer|string $customer
     * @return boolean
     */
    public function delete($customer): bool;

    /**
     * Convert a specified resource to collection.
     *
     * @param mixed $resource
     * @return mixed
     */
    public function toCollection($resource);
}
