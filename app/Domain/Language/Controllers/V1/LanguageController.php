<?php

namespace App\Domain\Language\Controllers\V1;

use App\Domain\Language\Queries\LanguageQueries;
use Illuminate\Http\JsonResponse;

class LanguageController
{
    public function __invoke(LanguageQueries $queries): JsonResponse
    {
        $resource = $queries->listOfLanguagesQuery()->get();

        return response()->json(
            data: $resource,
        );
    }
}
