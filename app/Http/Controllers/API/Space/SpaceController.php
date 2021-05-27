<?php

namespace App\Http\Controllers\API\Space;

use App\Http\Controllers\Controller;
use App\Http\Requests\Space\BatchUpdateSpaces;
use App\Models\Space;
use App\Queries\SpaceQueries;
use App\Services\Space\SpaceEntityService;
use Illuminate\Http\JsonResponse;

class SpaceController extends Controller
{
    /**
     * Show a list of existing space entities.
     *
     * @param \App\Queries\SpaceQueries $queries
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(SpaceQueries $queries): JsonResponse
    {
        return response()->json(
            $queries->listOfSpacesQuery()->get()
        );
    }

    /**
     * Batch put space entities.
     *
     * @param \App\Http\Requests\Space\BatchUpdateSpaces $request
     * @param \App\Services\Space\SpaceEntityService $entityService
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function batchPutSpaces(BatchUpdateSpaces $request, SpaceEntityService $entityService): JsonResponse
    {
        $this->authorize('create', Space::class);

        $resource = $entityService->batchPutSpaces(
            $request->getPutSpaceDataCollection()
        );

        return response()->json(
            $resource
        );
    }
}
