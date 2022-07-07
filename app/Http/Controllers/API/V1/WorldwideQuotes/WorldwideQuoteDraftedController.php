<?php

namespace App\Http\Controllers\API\V1\WorldwideQuotes;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorldwideQuote\PaginateWorldwideQuotes;
use App\Http\Resources\V1\WorldwideQuote\WorldwideQuoteDraft;
use App\Models\Quote\WorldwideQuote;
use App\Queries\WorldwideQuoteQueries;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorldwideQuoteDraftedController extends Controller
{
    /**
     * Display a listing of the existing alive drafted quotes.
     *
     * @param \App\Http\Requests\WorldwideQuote\PaginateWorldwideQuotes $request
     * @param WorldwideQuoteQueries $queries
     * @return AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function __invoke(PaginateWorldwideQuotes $request, WorldwideQuoteQueries $queries): AnonymousResourceCollection
    {
        $this->authorize('viewAny', WorldwideQuote::class);

        $pagination = $request->transformWorldwideQuotesQuery($queries->aliveDraftedListingQuery($request))->apiPaginate();

        return tap(WorldwideQuoteDraft::collection($pagination), function (AnonymousResourceCollection $resourceCollection) use ($pagination) {
            $resourceCollection->additional([
                'current_page' => $pagination->currentPage(),
                'from' => $pagination->firstItem(),
                'to' => $pagination->lastItem(),
                'last_page' => $pagination->lastPage(),
                'path' => $pagination->path(),
                'per_page' => $pagination->perPage(),
                'total' => $pagination->total(),
            ]);
        });
    }

    /**
     * Display a listing of the existing dead drafted quotes.
     *
     * @param PaginateWorldwideQuotes $request
     * @param WorldwideQuoteQueries $queries
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function paginateDeadDraftedQuotes(PaginateWorldwideQuotes $request, WorldwideQuoteQueries $queries): AnonymousResourceCollection
    {
        $this->authorize('viewAny', WorldwideQuote::class);

        $pagination = $request->transformWorldwideQuotesQuery($queries->deadDraftedListingQuery($request))->apiPaginate();

        return WorldwideQuoteDraft::collection($pagination);
    }
}
