<?php

namespace App\Http\Controllers\API;

use App\Queries\BusinessDivisionQueries;
use Illuminate\Http\JsonResponse;

class BusinessDivisionController
{
    /**
     * @param BusinessDivisionQueries $queries
     * @return JsonResponse
     */
    public function __invoke(BusinessDivisionQueries $queries): JsonResponse
    {
        return response()->json(
            $queries->businessDivisionsListQuery()->get()
        );
    }
}
