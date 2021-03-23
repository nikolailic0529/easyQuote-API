<?php

namespace App\Http\Controllers\API\WorldwideQuotes;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorldwideQuote\WorldwideQuoteDraft;
use App\Models\Quote\WorldwideQuote;
use App\Queries\WorldwideQuoteQueries;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorldwidePackQuoteDraftedController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param WorldwideQuoteQueries $queries
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function __invoke(Request $request, WorldwideQuoteQueries $queries): AnonymousResourceCollection
    {
        $this->authorize('viewAny', WorldwideQuote::class);

        $paginator = $queries->draftedListingQuery($request)->apiPaginate();

        return tap(WorldwideQuoteDraft::collection($paginator), function (AnonymousResourceCollection $resourceCollection) use ($paginator) {
            $resourceCollection->additional([
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'last_page' => $paginator->lastPage(),
                'path' => $paginator->path(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]);
        });
    }
}
