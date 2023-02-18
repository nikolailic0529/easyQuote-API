<?php

namespace App\Domain\BusinessDivision\Queries;

use App\Domain\BusinessDivision\Models\BusinessDivision;
use Illuminate\Database\Eloquent\Builder;

class BusinessDivisionQueries
{
    public function businessDivisionsListQuery(): Builder
    {
        return BusinessDivision::query();
    }
}
