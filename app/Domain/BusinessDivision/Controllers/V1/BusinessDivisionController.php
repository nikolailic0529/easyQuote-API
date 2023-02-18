<?php

namespace App\Domain\BusinessDivision\Controllers\V1;

use App\Domain\BusinessDivision\Queries\BusinessDivisionQueries;
use Illuminate\Http\JsonResponse;

class BusinessDivisionController
{
    public function __invoke(BusinessDivisionQueries $queries): JsonResponse
    {
        return response()->json(
            $queries->businessDivisionsListQuery()->get()
        );
    }
}
