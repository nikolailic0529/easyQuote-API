<?php

namespace App\Http\Controllers\API\Data;

use App\Http\Controllers\Controller;
use App\Queries\TimezoneQueries;
use Illuminate\Http\JsonResponse;

class TimezonesController extends Controller
{
    public function __invoke(TimezoneQueries $queries): JsonResponse
    {
        $resource = $queries->listOfTimezonesQuery()->get();

        return response()->json(
            data: $resource,
        );
    }
}
