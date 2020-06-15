<?php

namespace App\Facades;

use App\Contracts\Services\CustomerFlow as CustomerFlowContract;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void migrateCustomers()
 * @method static \App\Models\Company migrateCustomer(\App\Models\Customer\Customer $customer)
 *
 * @see \App\Contracts\Services\CustomerFlow
 */
class CustomerFlow extends Facade
{
    protected static function getFacadeAccessor()
    {
        return CustomerFlowContract::class;
    }
}