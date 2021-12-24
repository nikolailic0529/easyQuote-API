<?php

namespace App\Contracts\Services;

use App\DTO\EQCustomer\EQCustomerData;
use App\DTO\S4\S4CustomerData;
use App\Models\Customer\Customer;

interface CustomerState
{
    /**
     * Store a new customer from S4 data.
     *
     * @param \App\DTO\S4\S4CustomerData $data
     * @return \App\Models\Customer\Customer
     */
    public function createFromS4Data(S4CustomerData $data): Customer;

    /**
     * Store a new customer from EQ data.
     *
     * @param \App\DTO\EQCustomer\EQCustomerData $data
     * @return \App\Models\Customer\Customer
     */
    public function createFromEqData(EQCustomerData $data): Customer;

    /**
     * Update the specified customer from EQ data.
     *
     * @param Customer $customer
     * @param \App\DTO\EQCustomer\EQCustomerData $data
     * @return \App\Models\Customer\Customer
     */
    public function updateFromEqData(Customer $customer, EQCustomerData $data): Customer;

    /**
     * Delete the specified Customer & relations.
     *
     * @param \App\Models\Customer\Customer $customer
     * @return void
     */
    public function deleteCustomer(Customer $customer): void;
}