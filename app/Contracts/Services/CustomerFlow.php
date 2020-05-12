<?php

namespace App\Contracts\Services;

use App\Models\{
    Company,
    Customer\Customer,
};

interface CustomerFlow
{
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
