<?php

namespace App\Domain\Rescue\Contracts;

use App\Domain\Company\Enum\CompanyCategoryEnum;
use App\Domain\Company\Enum\CompanyType;
use App\Domain\Company\Models\Company;
use App\Domain\Rescue\Models\Customer;

interface MigratesCustomerEntity
{
    const COMPANY_TYPE = CompanyType::EXTERNAL;
    const COMPANY_CATEGORY = CompanyCategoryEnum::EndUser;

    /**
     * Migrate existing not migrated customers to external companies.
     */
    public function migrateCustomers(): void;

    /**
     * Migrate specific customer instance.
     */
    public function migrateCustomer(Customer $customer): Company;
}
