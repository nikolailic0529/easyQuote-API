<?php

namespace App\Services;

use App\Enum\CompanyType;
use App\Models\Company;
use App\Models\Customer\Customer;
use App\Services\Exceptions\EqCustomer;
use App\Services\Exceptions\InvalidCompany;
use Illuminate\Database\Eloquent\Builder;

class EqCustomerService
{
    protected const RFQ_NUMBER_SUFFIX = 'EQ';

    protected const RFQ_NUMBER_PATTERN = "%s-%s-%'.07d";

    /**
     * Give a new customer number based on the given internal company.
     *
     * @param Company $company
     * @param Customer|null $customer
     * @return string
     */
    public function giveNumber(Company $company, ?Customer $customer = null): string
    {
        if ($company->type !== CompanyType::INTERNAL) {
            throw InvalidCompany::nonInternal();
        }

        $highestNumber = $this->getHighestNumber($customer);

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
     * @param Customer|null $customer
     * @return integer
     */
    public function getHighestNumber(?Customer $customer = null): int
    {
        return (int) Customer::when($customer, fn (Builder $q) => $q->whereKeyNot($customer->getKey()))
            ->whereSource(Customer::EQ_SOURCE)
            ->max('sequence_number');
    }

    /**
     * Retrieve attributes for new Quote initiation from EQ Customer instance.
     *
     * @param Customer $customer
     * @return array
     *
     * @throws EqCustomer
     */
    public static function retrieveQuoteAttributes(Customer $customer): array
    {
        return [
            'customer_id' => $customer->getKey(),
            'company_id' => $customer->int_company_id,
            'last_drafted_step' => 'Customer'
        ];
    }
}
