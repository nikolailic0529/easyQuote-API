<?php

namespace App\Domain\Language\Controllers\V1;

use App\Domain\Language\Queries\LanguageQueries;
use App\Domain\Language\Resources\V1\LanguageResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LanguageController
{
    public function __invoke(LanguageQueries $queries): JsonResponse
    {
        return response()->json(
            data: $queries->listLanguagesQuery()->get(),
        );
    }

    public function listContactLanguages(LanguageQueries $queries): AnonymousResourceCollection
    {
        return LanguageResource::collection($queries->listContactLanguagesQuery()->get());
    }
}
