<?php

namespace App\Domain\Rescue\Queries;

use App\Domain\Rescue\Models\Customer;
use Illuminate\Database\Eloquent\Builder;

class CustomerQueries
{
    public function listingQuery(): Builder
    {
        return Customer::doesntHave('quotes')
            ->orderBy('created_at', 'desc');
    }
}
