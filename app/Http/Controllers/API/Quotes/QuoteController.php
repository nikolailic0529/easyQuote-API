<?php

namespace App\Http\Controllers\API\Quotes;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\{
    Quote\QuoteRepositoryInterface as QuoteRepository,
    Quote\Margin\MarginRepositoryInterface as MarginRepository,
    CompanyRepositoryInterface as CompanyRepository,
    CurrencyRepositoryInterface as CurrencyRepository,
    QuoteTemplate\QuoteTemplateRepositoryInterface as QuoteTemplateRepository,
    QuoteFile\DataSelectSeparatorRepositoryInterface as DataSelectRepository
};
use App\Http\Requests\{
    Quote\StoreQuoteStateRequest,
    GetQuoteTemplatesRequest,
    MappingReviewRequest,
    Quote\MoveGroupDescriptionRowsRequest,
    Quote\StoreGroupDescriptionRequest,
    Quote\UpdateGroupDescriptionRequest
};
use App\Http\Requests\Quote\SetVersionRequest;
use App\Http\Requests\Quote\TryDiscountsRequest;
use App\Http\Resources\QuoteVersionResource;
use App\Http\Resources\TemplateRepository\TemplateResourceListing;
use App\Models\Quote\Quote;
use Setting;

class QuoteController extends Controller
{
    /** @var \App\Contracts\Repositories\Quote\QuoteRepositoryInterface */
    protected $quotes;

    /** @var \App\Contracts\Repositories\QuoteTemplate\QuoteTemplateRepositoryInterface */
    protected $quoteTemplates;

    /** @var \App\Contracts\Repositories\Quote\Margin\MarginRepositoryInterface */
    protected $margins;

    /** @var \App\Contracts\Repositories\CompanyRepositoryInterface */
    protected $companies;

    /** @var \App\Contracts\Repositories\CurrencyRepositoryInterface */
    protected $currencies;

    /** @var \App\Contracts\Repositories\QuoteFile\DataSelectSeparatorRepositoryInterface */
    protected $dataSelects;

    public function __construct(
        QuoteRepository $quotes,
        QuoteTemplateRepository $quoteTemplates,
        MarginRepository $margins,
        CompanyRepository $companies,
        DataSelectRepository $dataSelects,
        CurrencyRepository $currencies
    ) {
        $this->quotes = $quotes;
        $this->quoteTemplates = $quoteTemplates;
        $this->margins = $margins;
        $this->companies = $companies;
        $this->dataSelects = $dataSelects;
        $this->currencies = $currencies;
    }

    public function quote(Quote $quote)
    {
        $this->authorize('view', $quote);

        $resource = $this->quotes->find($quote);

        return response()->json(
            filter(QuoteVersionResource::make($resource))
        );
    }

    public function storeState(StoreQuoteStateRequest $request)
    {
        if ($request->has('quote_id')) {
            $this->authorize('state', $request->quote());
        } else {
            $this->authorize('create', Quote::class);
        }

        return response()->json(
            $this->quotes->storeState($request)
        );
    }

    public function setVersion(SetVersionRequest $request, Quote $quote)
    {
        $this->authorize('update', $quote);

        return response()->json(
            $this->quotes->setVersion($request->version_id, $quote)
        );
    }

    public function step1()
    {
        return response()->json(
            [
                'companies'                 => $this->companies->allWithVendorsAndCountries(),
                'data_select_separators'    => $this->dataSelects->all(),
                'supported_file_types'      => Setting::get('supported_file_types_ui'),
                'currencies'                => $this->currencies->allHaveExrate()
            ]
        );
    }

    public function step2(MappingReviewRequest $request)
    {
        $this->authorize('view', $this->quotes->find($request->quote_id));

        if ($request->has('search')) {
            return response()->json(
                $this->quotes->rows($request->quote_id, $request->search, $request->group_id)
            );
        }

        return response()->json(
            $this->quotes->step2($request)
        );
    }

    /**
     * Show Grouped Rows.
     *
     * @param Quote $quote
     * @return \Illuminate\Http\Response
     */
    public function rowsGroups(Quote $quote)
    {
        $this->authorize('view', $quote);

        return response()->json(
            $this->quotes->rowsGroups($quote->id)
        );
    }

    public function templates(GetQuoteTemplatesRequest $request)
    {
        $resource = $request->repository()->findByCompanyVendorCountry($request);

        return response()->json(TemplateResourceListing::collection($resource));
    }

    public function step3()
    {
        return response()->json(
            $this->margins->data()
        );
    }

    /**
     * Get acceptable Discounts for the specified Quote
     *
     * @param Quote $quote
     * @return \Illuminate\Http\Response
     */
    public function discounts(Quote $quote)
    {
        $this->authorize('view', $quote);

        return response()->json(
            $this->quotes->discounts($quote->id)
        );
    }

    /**
     * Try Apply Discounts to the Quote List Price.
     * Return passed discounts with calculated Total Margin after each passed Discount.
     *
     * @param TryDiscountsRequest $request
     * @param Quote $quote
     * @return \Illuminate\Http\Response
     */
    public function tryDiscounts(TryDiscountsRequest $request, Quote $quote)
    {
        $this->authorize('view', $quote);

        return response()->json(
            $this->quotes->tryDiscounts($request, $quote->id)
        );
    }

    /**
     * Get Imported Rows Data after Applying Margins
     *
     * @param Quote $quote
     * @return \Illuminate\Http\Response
     */
    public function review(Quote $quote)
    {
        $this->authorize('view', $quote);

        return response()->json(
            $this->quotes->review($quote->id)
        );
    }

    /**
     * Show specified Rows Group Description from specified Quote.
     *
     * @param Quote $quote
     * @param string $group
     * @return \Illuminate\Http\Request
     */
    public function showGroupDescription(Quote $quote, string $group)
    {
        $this->authorize('view', $quote);

        return response()->json(
            $this->quotes->findGroupDescription($group, $quote->id)
        );
    }

    /**
     * Store Rows Group Description for specified Quote.
     *
     * @param StoreGroupDescriptionRequest $request
     * @param Quote $quote
     * @return \Illuminate\Http\Response
     */
    public function storeGroupDescription(StoreGroupDescriptionRequest $request, Quote $quote)
    {
        $this->authorize('update', $quote);

        return response()->json(
            $this->quotes->createGroupDescription($request, $quote->id)
        );
    }

    /**
     * Store Rows Group Description for specified Quote.
     *
     * @param UpdateGroupDescriptionRequest $request
     * @param Quote $quote
     * @param string $group
     * @return \Illuminate\Http\Response
     */
    public function updateGroupDescription(UpdateGroupDescriptionRequest $request, Quote $quote, string $group)
    {
        $this->authorize('update', $quote);

        return response()->json(
            $this->quotes->updateGroupDescription($request, $group, $quote->id)
        );
    }

    /**
     * Move specified Rows to specified Rows Group Description for specified Quote.
     *
     * @param MoveGroupDescriptionRowsRequest $request
     * @param Quote $quote
     * @return \Illuminate\Http\Response
     */
    public function moveGroupDescriptionRows(MoveGroupDescriptionRowsRequest $request, Quote $quote)
    {
        $this->authorize('update', $quote);

        return response()->json(
            $this->quotes->moveGroupDescriptionRows($request, $quote->id)
        );
    }

    /**
     * Remove specified Rows Group Description from specified Quote.
     *
     * @param Quote $quote
     * @param string $group
     * @return \Illuminate\Http\Response
     */
    public function destroyGroupDescription(Quote $quote, string $group)
    {
        $this->authorize('update', $quote);

        return response()->json(
            $this->quotes->deleteGroupDescription($group, $quote->id)
        );
    }
}
