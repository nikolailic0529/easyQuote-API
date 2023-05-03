<?php

namespace App\Domain\QuoteFile\Controllers\V1;

use App\Domain\QuoteFile\Queries\FileFormatQueries;
use App\Foundation\Http\Controller;
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
