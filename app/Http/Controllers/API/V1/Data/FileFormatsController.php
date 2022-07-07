<?php

namespace App\Http\Controllers\API\V1\Data;

use App\Http\Controllers\Controller;
use App\Queries\FileFormatQueries;
use Illuminate\Http\JsonResponse;

class FileFormatsController extends Controller
{
    public function __invoke(FileFormatQueries $queries): JsonResponse
    {
        $resource = $queries->listOfFileFormatsQuery()->get();

        return response()->json(
            data: $resource,
        );
    }
}
