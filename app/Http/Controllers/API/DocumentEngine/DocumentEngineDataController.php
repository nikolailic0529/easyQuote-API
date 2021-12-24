<?php

namespace App\Http\Controllers\API\DocumentEngine;

use App\Http\Middleware\CheckClientCredentials;
use App\Queries\ImportableColumnQueries;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DocumentEngineDataController
{
    /**
     * The middleware registered on the controller.
     *
     * @var array
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
     *
     * @param ImportableColumnQueries $columnQueries
     * @return JsonResponse
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