<?php

namespace App\Foundation\Support\Elasticsearch\Controllers\V1;

use App\Foundation\Http\Controller;
use App\Foundation\Support\Elasticsearch\Services\RebuildSearchQueueService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SearchController extends Controller
{
    /**
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
