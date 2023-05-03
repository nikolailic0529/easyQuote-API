<?php

namespace App\Domain\Rescue\Facades;

use App\Domain\Rescue\Contracts\MigratesCustomerEntity as CustomerFlowContract;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void                               migrateCustomers()
 * @method static \App\Domain\Company\Models\Company migrateCustomer(\App\Domain\Rescue\Models\Customer $customer)
 *
 * @see \App\Domain\Rescue\Contracts\MigratesCustomerEntity
 */
class CustomerFlow extends Facade
{
    protected static function getFacadeAccessor()
    {
        return CustomerFlowContract::class;
    }
}
