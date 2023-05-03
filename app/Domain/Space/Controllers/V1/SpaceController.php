<?php

namespace App\Domain\Space\Controllers\V1;

use App\Domain\Space\Models\Space;
use App\Domain\Space\Queries\SpaceQueries;
use App\Domain\Space\Requests\BatchUpdateSpacesRequest;
use App\Domain\Space\Services\SpaceEntityService;
use App\Foundation\Http\Controller;
use Illuminate\Http\JsonResponse;

class SpaceController extends Controller
{
    /**
     * Show a list of existing space entities.
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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function batchPutSpaces(BatchUpdateSpacesRequest $request, SpaceEntityService $entityService): JsonResponse
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
