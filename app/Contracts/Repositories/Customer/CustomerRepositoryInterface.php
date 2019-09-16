<?php namespace App\Contracts\Repositories\Customer;

interface CustomerRepositoryInterface
{
    /**
     * Get all S4 customers
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all();
}
