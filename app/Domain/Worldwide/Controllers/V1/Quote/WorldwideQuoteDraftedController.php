<?php

namespace App\Domain\Worldwide\Controllers\V1\Quote;

use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Queries\WorldwideQuoteQueries;
use App\Domain\Worldwide\Requests\Quote\PaginateWorldwideQuotesRequest;
use App\Domain\Worldwide\Resources\V1\Quote\WorldwideQuoteDraft;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorldwideQuoteDraftedController extends Controller
{
    /**
     * Display a listing of the existing alive drafted quotes.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function __invoke(PaginateWorldwideQuotesRequest $request, WorldwideQuoteQueries $queries): AnonymousResourceCollection
    {
        $this->authorize('viewAny', WorldwideQuote::class);

        $pagination = $queries->aliveDraftedListingQuery($request)->apiPaginate();

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
     * @throws AuthorizationException
     */
    public function paginateDeadDraftedQuotes(PaginateWorldwideQuotesRequest $request, WorldwideQuoteQueries $queries): AnonymousResourceCollection
    {
        $this->authorize('viewAny', WorldwideQuote::class);

        $pagination = $queries->deadDraftedListingQuery($request)->apiPaginate();

        return WorldwideQuoteDraft::collection($pagination);
    }
}
