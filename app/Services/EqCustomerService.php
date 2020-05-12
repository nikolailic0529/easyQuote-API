<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Customer\Customer;
use App\Services\Exceptions\InvalidCompany;

class EqCustomerService
{
    protected const RFQ_NUMBER_SUFFIX = 'EQ';

    protected const RFQ_NUMBER_PATTERN = "%s-%s-%'.07d";

    protected Customer $customer;

    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * Give a new customer number based on the given internal company.
     *
     * @param Company $company
     * @return string
     */
    public function giveNumber(Company $company): string
    {
        if ($company->type !== Company::INT_TYPE) {
            throw InvalidCompany::nonInternal();
        }

        $highestNumber = $this->getHighestNumber();

        return sprintf(
            static::RFQ_NUMBER_PATTERN,
            $company->short_code,
            static::RFQ_NUMBER_SUFFIX,
            ++$highestNumber
        );
    }

    /**
     * Retrieve the highest eq customer number.
     *
     * @return integer
     */
    public function getHighestNumber(): int
    {
        return (int) $this->customer->whereSource(Customer::EQ_SOURCE)->max('sequence_number');
    }
}
