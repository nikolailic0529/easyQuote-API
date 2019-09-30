<?php namespace App\Traits;

use App\Models\Customer\Customer;

trait BelongsToCustomer
{
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
