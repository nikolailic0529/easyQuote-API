<?php

namespace App\Domain\Timezone\Controllers\V1;

use App\Domain\Timezone\Queries\TimezoneQueries;
use App\Foundation\Http\Controller;
use Illuminate\Http\JsonResponse;

class TimezoneController extends Controller
{
    public function __invoke(TimezoneQueries $queries): JsonResponse
    {
        $resource = $queries->listOfTimezonesQuery()->get();

        return response()->json(
            data: $resource,
        );
    }
}
