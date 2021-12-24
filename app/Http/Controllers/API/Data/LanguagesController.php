<?php

namespace App\Http\Controllers\API\Data;

use App\Queries\LanguageQueries;
use Illuminate\Http\JsonResponse;

class LanguagesController
{
    public function __invoke(LanguageQueries $queries): JsonResponse
    {
        $resource = $queries->listOfLanguagesQuery()->get();

        return response()->json(
            data: $resource,
        );
    }
}
