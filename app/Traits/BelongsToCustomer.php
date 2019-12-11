<?php

namespace App\Traits;

use App\Models\Customer\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToCustomer
{
    public function customer()
    {
        return $this->belongsTo(Customer::class)->withDefault(Customer::make([]));
    }

    public function scopeRfq(Builder $query, string $rfq): Builder
    {
        return $query->whereHas('customer', function ($query) use ($rfq) {
            $query->whereRfq($rfq);
        });
    }
}
