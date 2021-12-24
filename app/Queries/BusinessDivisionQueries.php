<?php

namespace App\Queries;

use App\Models\BusinessDivision;
use Illuminate\Database\Eloquent\Builder;

class BusinessDivisionQueries
{
    public function businessDivisionsListQuery(): Builder
    {
        return BusinessDivision::query();
    }
}
