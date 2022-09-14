<?php

namespace App\Http\Controllers\API\V1\WorldwideQuotes;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorldwideQuote\PaginateWorldwideQuotes;
use App\Http\Resources\V1\WorldwideQuote\SubmittedWorldwideQuote;
use App\Models\Quote\WorldwideQuote;
use App\Queries\WorldwideQuoteQueries;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorldwideQuoteSubmittedController extends Controller
{
    /**
     * Display a listing of the existing alive submitted quotes.
     *
     * @param PaginateWorldwideQuotes $request
     * @param WorldwideQuoteQueries $queries
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function __invoke(PaginateWorldwideQuotes $request, WorldwideQuoteQueries $queries): AnonymousResourceCollection
    {
        $this->authorize('viewAny', WorldwideQuote::class);

        $pagination = $queries->aliveSubmittedListingQuery($request)->apiPaginate();

        return tap(SubmittedWorldwideQuote::collection($pagination), function (AnonymousResourceCollection $resourceCollection) use ($pagination) {
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
     * Display a listing of the existing dead submitted quotes.
     *
     * @param PaginateWorldwideQuotes $request
     * @param WorldwideQuoteQueries $queries
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function paginateDeadSubmittedQuotes(PaginateWorldwideQuotes $request, WorldwideQuoteQueries $queries): AnonymousResourceCollection
    {
        $this->authorize('viewAny', WorldwideQuote::class);

        $pagination = $queries->deadSubmittedListingQuery($request)->apiPaginate();

        return SubmittedWorldwideQuote::collection($pagination);
    }
}
