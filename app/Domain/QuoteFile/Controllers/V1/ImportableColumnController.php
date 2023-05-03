<?php

namespace App\Domain\QuoteFile\Controllers\V1;

use App\Domain\QuoteFile\Models\ImportableColumn;
use App\Domain\QuoteFile\Queries\ImportableColumnQueries;
use App\Domain\QuoteFile\Requests\CreateImportableColumnRequest;
use App\Domain\QuoteFile\Requests\UpdateImportableColumnRequest;
use App\Domain\QuoteFile\Resources\V1\ImportableColumnWithIncludes;
use App\Domain\QuoteFile\Resources\V1\{ImportableColumnCollection};
use App\Domain\QuoteFile\Services\ImportableColumnEntityService;
use App\Foundation\Http\Controller;
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
     */
    public function show(ImportableColumn $importableColumn): JsonResponse
    {
        return response()->json(
            filter(ImportableColumnWithIncludes::make($importableColumn))
        );
    }

    /**
     * Update the specified importable column entity.
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
