<?php

namespace App\Domain\DocumentEngine\Controllers\V1;

use App\Domain\QuoteFile\Queries\ImportableColumnQueries;
use App\Foundation\Http\Middleware\CheckClientCredentials;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DocumentEngineDataController
{
    /**
     * The middleware registered on the controller.
     */
    protected array $middleware = [];

    public function __construct()
    {
        $this->middleware[] = [
            'middleware' => CheckClientCredentials::class,
            'options' => [],
        ];
    }

    /**
     * Show importable column entities linked with document headers on document engine api.
     */
    public function showLinkedDocumentHeaders(ImportableColumnQueries $columnQueries): JsonResponse
    {
        $resource = $columnQueries->documentEngineLinkedImportableColumnsQuery()->get();

        return response()->json(
            data: $resource,
            status: Response::HTTP_OK,
        );
    }
}
