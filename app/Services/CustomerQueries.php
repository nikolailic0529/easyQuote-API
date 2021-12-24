<?php

namespace App\Services;

use App\Models\Customer\Customer;
use Illuminate\Database\Eloquent\Builder;

class CustomerQueries
{
    public function listingQuery(): Builder
    {
        return Customer::doesntHave('quotes')
            ->orderBy('created_at', 'desc');
    }
}