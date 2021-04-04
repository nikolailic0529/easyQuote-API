<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\UnifiedQuote\UnifiedQuotesRequest;
use App\Http\Resources\UnifiedQuote\ExpiringUnifiedQuote;
use App\Http\Resources\UnifiedQuote\UnifiedQuote;
use App\Queries\UnifiedQuoteQueries;
use App\Services\UnifiedQuote\UnifiedQuoteDataMapper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UnifiedQuoteController extends Controller
{
    protected Gate $gate;

    public function __construct(Gate $gate)
    {
        $this->gate = $gate;
    }

    /**
     * Paginate existing unified expiring quotes.
     *
     * @param UnifiedQuotesRequest $request
     * @param UnifiedQuoteQueries $queries
     * @param UnifiedQuoteDataMapper $dataMapper
     * @return AnonymousResourceCollection
     */
    public function paginateUnifiedExpiringQuotes(UnifiedQuotesRequest $request,
                                                  UnifiedQuoteQueries $queries,
                                                  UnifiedQuoteDataMapper $dataMapper): AnonymousResourceCollection
    {

        if ($this->gate->denies('viewQuotesOfAnyBusinessDivision')) {
            return ExpiringUnifiedQuote::collection([]);
        }

        $pagination = $queries->paginateExpiringQuotesQuery($request->getUnifiedQuotesRequestData())->apiPaginate();

        $pagination = $dataMapper->mapUnifiedQuotePaginator($pagination);

        return ExpiringUnifiedQuote::collection($pagination);
    }

    /**
     * Paginate existing unified drafted quotes.
     *
     * @param UnifiedQuotesRequest $request
     * @param UnifiedQuoteQueries $queries
     * @param UnifiedQuoteDataMapper $dataMapper
     * @return AnonymousResourceCollection
     */
    public function paginateUnifiedDraftedQuotes(UnifiedQuotesRequest $request,
                                                 UnifiedQuoteQueries $queries,
                                                 UnifiedQuoteDataMapper $dataMapper): AnonymousResourceCollection
    {
        if ($this->gate->denies('viewQuotesOfAnyBusinessDivision')) {
            return UnifiedQuote::collection([]);
        }

        /** @var LengthAwarePaginator $pagination */
        $pagination = $queries->paginateDraftedQuotesQuery($request->getUnifiedQuotesRequestData())->apiPaginate();

        $pagination = $dataMapper->mapUnifiedQuotePaginator($pagination);

        return UnifiedQuote::collection($pagination);
    }

    /**
     * Paginate existing unified submitted quotes.
     *
     * @param UnifiedQuotesRequest $request
     * @param UnifiedQuoteQueries $queries
     * @param UnifiedQuoteDataMapper $dataMapper
     * @return AnonymousResourceCollection
     */
    public function paginateUnifiedSubmittedQuotes(UnifiedQuotesRequest $request,
                                                   UnifiedQuoteQueries $queries,
                                                   UnifiedQuoteDataMapper $dataMapper): AnonymousResourceCollection
    {
        if ($this->gate->denies('viewQuotesOfAnyBusinessDivision')) {
            return UnifiedQuote::collection([]);
        }

        $pagination = $queries->paginateSubmittedQuotesQuery($request->getUnifiedQuotesRequestData())->apiPaginate();

        $pagination = $dataMapper->mapUnifiedQuotePaginator($pagination);

        return UnifiedQuote::collection($pagination);
    }
}
