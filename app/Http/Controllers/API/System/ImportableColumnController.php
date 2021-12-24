<?php

namespace App\Http\Controllers\API\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportableColumn\{CreateImportableColumnRequest, UpdateImportableColumnRequest};
use App\Http\Resources\ImportableColumn\{ImportableColumnCollection, ImportableColumnWithIncludes};
use App\Models\QuoteFile\ImportableColumn;
use App\Queries\ImportableColumnQueries;
use App\Services\ImportableColumn\ImportableColumnEntityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ImportableColumnController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ImportableColumn::class, 'importable_column');
    }

    /**
     * Show a paginated listing of the existing importable column entities.
     *
     * @param Request $request
     * @param ImportableColumnQueries $importableColumnQueries
     * @return JsonResponse
     */
    public function index(Request $request,
                          ImportableColumnQueries $importableColumnQueries): JsonResponse
    {
        $pagination = $importableColumnQueries->listOfImportableColumnsQuery(request: $request)->apiPaginate();

        return response()->json(
            ImportableColumnCollection::make($pagination)
        );
    }

    /**
     * Create a new importable column entity.
     *
     * @param \App\Http\Requests\ImportableColumn\CreateImportableColumnRequest $request
     * @param ImportableColumnEntityService $entityService
     * @return JsonResponse
     */
    public function store(CreateImportableColumnRequest $request,
                          ImportableColumnEntityService $entityService): JsonResponse
    {
        $resource = $entityService
            ->setCauser($request->user())
            ->createColumn(data: $request->getCreateColumnData());

        return response()->json(
            data: filter(ImportableColumnWithIncludes::make($resource)),
            status: Response::HTTP_CREATED
        );
    }

    /**
     * Show the specified importable column entity.
     *
     * @param ImportableColumn $importableColumn
     * @return JsonResponse
     */
    public function show(ImportableColumn $importableColumn): JsonResponse
    {
        return response()->json(
            filter(ImportableColumnWithIncludes::make($importableColumn))
        );
    }

    /**
     * Update the specified importable column entity.
     *
     * @param UpdateImportableColumnRequest $request
     * @param ImportableColumnEntityService $entityService
     * @param ImportableColumn $importableColumn
     * @return JsonResponse
     */
    public function update(UpdateImportableColumnRequest $request,
                           ImportableColumnEntityService $entityService,
                           ImportableColumn $importableColumn): JsonResponse
    {

        $resource = $entityService
            ->setCauser($request->user())
            ->updateColumn(
                column: $importableColumn,
                data: $request->getUpdateColumnData()
            );

        return response()->json(
            filter(ImportableColumnWithIncludes::make($resource))
        );
    }

    /**
     * Remove the specified importable column entity.
     *
     * @param Request $request
     * @param ImportableColumnEntityService $entityService
     * @param ImportableColumn $importableColumn
     * @return JsonResponse
     */
    public function destroy(Request $request,
                            ImportableColumnEntityService $entityService,
                            ImportableColumn $importableColumn): JsonResponse
    {
        $entityService
            ->setCauser($request->user())
            ->deleteColumn(column: $importableColumn);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * Mark as active the specified importable column entity.
     *
     * @param Request $request
     * @param ImportableColumnEntityService $entityService
     * @param ImportableColumn $importableColumn
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function activate(Request $request,
                             ImportableColumnEntityService $entityService,
                             ImportableColumn $importableColumn): JsonResponse
    {
        $this->authorize('activate', $importableColumn);

        $entityService
            ->setCauser($request->user())
            ->markColumnAsActive(column: $importableColumn);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * Deactivate the specified resource in storage.
     *
     * @param Request $request
     * @param ImportableColumnEntityService $entityService
     * @param ImportableColumn $importableColumn
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function deactivate(Request $request,
                               ImportableColumnEntityService $entityService,
                               ImportableColumn $importableColumn): JsonResponse
    {
        $this->authorize('deactivate', $importableColumn);

        $entityService
            ->setCauser($request->user())
            ->markColumnAsInactive(column: $importableColumn);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}
