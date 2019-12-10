<?php

namespace App\Traits;

use App\Models\Customer\Customer;
use Illuminate\Database\Eloquent\Model;

trait BelongsToCustomer
{
    public function customer()
    {
        return $this->belongsTo(Customer::class)->withDefault(Customer::make([]));
    }
}
