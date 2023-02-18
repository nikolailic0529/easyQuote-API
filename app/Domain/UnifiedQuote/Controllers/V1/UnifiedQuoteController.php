<?php

namespace App\Domain\UnifiedQuote\Controllers\V1;

use App\Domain\UnifiedQuote\Queries\UnifiedQuoteQueries;
use App\Domain\UnifiedQuote\Requests\UnifiedQuotesRequest;
use App\Domain\UnifiedQuote\Resources\V1\ExpiringUnifiedQuote;
use App\Domain\UnifiedQuote\Resources\V1\UnifiedQuote;
use App\Domain\UnifiedQuote\Services\UnifiedQuoteDataMapper;
use App\Foundation\Http\Controller;
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
     */
    public function paginateUnifiedExpiringQuotes(UnifiedQuotesRequest $request,
                                                  UnifiedQuoteQueries $queries,
                                                  UnifiedQuoteDataMapper $dataMapper): AnonymousResourceCollection
    {
        if ($this->gate->denies('viewQuotesOfAnyBusinessDivision')) {
            return ExpiringUnifiedQuote::collection([]);
        }

        $pagination = $queries->paginateExpiringQuotesQuery(requestData: $request->getUnifiedQuotesRequestData(), request: $request)->apiPaginate();

        $pagination = $dataMapper->mapUnifiedQuotePaginator($pagination);

        return ExpiringUnifiedQuote::collection($pagination);
    }

    /**
     * Paginate existing unified drafted quotes.
     */
    public function paginateUnifiedDraftedQuotes(UnifiedQuotesRequest $request,
                                                 UnifiedQuoteQueries $queries,
                                                 UnifiedQuoteDataMapper $dataMapper): AnonymousResourceCollection
    {
        if ($this->gate->denies('viewQuotesOfAnyBusinessDivision')) {
            return UnifiedQuote::collection([]);
        }

        /** @var LengthAwarePaginator $pagination */
        $pagination = $queries->paginateDraftedQuotesQuery(requestData: $request->getUnifiedQuotesRequestData(), request: $request)->apiPaginate();

        $pagination = $dataMapper->mapUnifiedQuotePaginator($pagination);

        return UnifiedQuote::collection($pagination);
    }

    /**
     * Paginate existing unified submitted quotes.
     */
    public function paginateUnifiedSubmittedQuotes(UnifiedQuotesRequest $request,
                                                   UnifiedQuoteQueries $queries,
                                                   UnifiedQuoteDataMapper $dataMapper): AnonymousResourceCollection
    {
        if ($this->gate->denies('viewQuotesOfAnyBusinessDivision')) {
            return UnifiedQuote::collection([]);
        }

        $pagination = $queries->paginateSubmittedQuotesQuery(requestData: $request->getUnifiedQuotesRequestData(), request: $request)->apiPaginate();

        $pagination = $dataMapper->mapUnifiedQuotePaginator($pagination);

        return UnifiedQuote::collection($pagination);
    }
}
