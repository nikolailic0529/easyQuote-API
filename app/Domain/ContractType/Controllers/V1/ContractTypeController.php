<?php

namespace App\Domain\ContractType\Controllers\V1;

use App\Domain\ContractType\Queries\ContractTypeQueries;
use Illuminate\Http\JsonResponse;

class ContractTypeController
{
    public function __invoke(ContractTypeQueries $queries): JsonResponse
    {
        return response()->json(
            $queries->contractTypesListQuery()->get()
        );
    }
}
