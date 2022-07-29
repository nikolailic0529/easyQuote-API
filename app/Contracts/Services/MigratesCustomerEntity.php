<?php

namespace App\Contracts\Services;

use App\Enum\CompanyCategoryEnum;
use App\Enum\CompanyType;
use App\Models\{Company, Customer\Customer,};

interface MigratesCustomerEntity
{
    const COMPANY_TYPE = CompanyType::EXTERNAL;
    const COMPANY_CATEGORY = CompanyCategoryEnum::EndUser;

    /**
     * Migrate existing not migrated customers to external companies.
     *
     * @return void
     */
    public function migrateCustomers(): void;

    /**
     * Migrate specific customer instance.
     *
     * @param Customer $customer
     * @return Company
     */
    public function migrateCustomer(Customer $customer): Company;
}
