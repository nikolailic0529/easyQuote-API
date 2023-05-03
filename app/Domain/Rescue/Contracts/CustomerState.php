<?php

namespace App\Domain\Rescue\Contracts;

use App\Domain\Rescue\DataTransferObjects\EQCustomerData;
use App\Domain\Rescue\DataTransferObjects\S4CustomerData;
use App\Domain\Rescue\Models\Customer;

interface CustomerState
{
    /**
     * Store a new customer from S4 data.
     */
    public function createFromS4Data(S4CustomerData $data): Customer;

    /**
     * Store a new customer from EQ data.
     */
    public function createFromEqData(EQCustomerData $data): Customer;

    /**
     * Update the specified customer from EQ data.
     */
    public function updateFromEqData(Customer $customer, EQCustomerData $data): Customer;

    /**
     * Delete the specified Customer & relations.
     */
    public function deleteCustomer(Customer $customer): void;
}
