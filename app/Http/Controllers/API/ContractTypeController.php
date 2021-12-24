<?php

namespace App\Http\Controllers\API;

use App\Queries\ContractTypeQueries;
use Illuminate\Http\JsonResponse;

class ContractTypeController
{
    /**
     * @param ContractTypeQueries $queries
     * @return JsonResponse
     */
    public function __invoke(ContractTypeQueries $queries): JsonResponse
    {
        return response()->json(
            $queries->contractTypesListQuery()->get()
        );
    }
}
