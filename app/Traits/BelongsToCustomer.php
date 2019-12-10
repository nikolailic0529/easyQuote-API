<?php

namespace App\Traits;

use App\Models\Customer\Customer;
use Illuminate\Database\Eloquent\Model;

trait BelongsToCustomer
{
    public static function bootBelongsToCustomer()
    {
        static::replicating(function (Model $model) {
            $model->customer_id = null;
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withDefault(Customer::make([]));
    }
}
