<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UnifiedQuote\ExpiringUnifiedQuote;
use App\Http\Resources\UnifiedQuote\UnifiedQuote;
use App\Queries\UnifiedQuoteQueries;
use App\Services\UnifiedQuote\UnifiedQuoteDataMapper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UnifiedQuoteController extends Controller
{
    /**
     * Paginate existing unified expiring quotes.
     *
     * @param Request $request
     * @param UnifiedQuoteQueries $queries
     * @return AnonymousResourceCollection
     */
    public function paginateUnifiedExpiringQuotes(Request $request, UnifiedQuoteQueries $queries, UnifiedQuoteDataMapper $dataMapper): AnonymousResourceCollection
    {
        $pagination = $queries->paginateExpiringQuotesQuery($request)->apiPaginate();

        $pagination = $dataMapper->mapUnifiedQuotePaginator($pagination);

        return ExpiringUnifiedQuote::collection($pagination);
    }

    /**
     * Paginate existing unified drafted quotes.
     *
     * @param Request $request
     * @param UnifiedQuoteQueries $queries
     * @param UnifiedQuoteDataMapper $dataMapper
     * @return AnonymousResourceCollection
     */
    public function paginateUnifiedDraftedQuotes(Request $request, UnifiedQuoteQueries $queries, UnifiedQuoteDataMapper $dataMapper): AnonymousResourceCollection
    {
        /** @var LengthAwarePaginator $pagination */
        $pagination = $queries->paginateDraftedQuotesQuery($request)->apiPaginate();

        $pagination = $dataMapper->mapUnifiedQuotePaginator($pagination);

        return UnifiedQuote::collection($pagination);
    }

    /**
     * Paginate existing unified submitted quotes.
     *
     * @param Request $request
     * @param UnifiedQuoteQueries $queries
     * @param UnifiedQuoteDataMapper $dataMapper
     * @return AnonymousResourceCollection
     */
    public function paginateUnifiedSubmittedQuotes(Request $request, UnifiedQuoteQueries $queries, UnifiedQuoteDataMapper $dataMapper): AnonymousResourceCollection
    {
        $pagination = $queries->paginateSubmittedQuotesQuery($request)->apiPaginate();

        $pagination = $dataMapper->mapUnifiedQuotePaginator($pagination);

        return UnifiedQuote::collection($pagination);
    }
}
