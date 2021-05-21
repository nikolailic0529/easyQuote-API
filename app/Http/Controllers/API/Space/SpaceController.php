<?php

namespace App\Http\Controllers\API\Space;

use App\Http\Controllers\Controller;
use App\Queries\SpaceQueries;
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
}
