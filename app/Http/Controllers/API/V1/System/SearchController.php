<?php

namespace App\Http\Controllers\API\V1\System;

use App\Http\Controllers\Controller;
use App\Services\Elasticsearch\RebuildSearchQueueService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SearchController extends Controller
{
    /**
     * @param  Request  $request
     * @param  RebuildSearchQueueService  $service
     * @return Response
     * @throws AuthorizationException
     */
    public function queueSearchRebuild(
        Request $request,
        RebuildSearchQueueService $service
    ): Response {
        $this->authorize('rebuildSearch');

        $service
            ->setCauser($request->user())
            ->queueSearchRebuild();

        return response()->noContent();
    }
}