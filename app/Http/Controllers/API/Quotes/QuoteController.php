<?php

namespace App\Http\Controllers\API\Quotes;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\{
    Quote\QuoteRepositoryInterface as QuoteRepository,
    Quote\Margin\MarginRepositoryInterface as MarginRepository,
    CompanyRepositoryInterface as CompanyRepository,
    QuoteTemplate\QuoteTemplateRepositoryInterface as QuoteTemplateRepository,
    QuoteFile\DataSelectSeparatorRepositoryInterface as DataSelectRepository
};
use App\Http\Requests\{
    StoreQuoteStateRequest,
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
    protected $quote;

    protected $template;

    protected $margin;

    protected $company;

    protected $dataSelect;

    public function __construct(
        QuoteRepository $quote,
        QuoteTemplateRepository $quoteTemplates,
        MarginRepository $margin,
        CompanyRepository $company,
        DataSelectRepository $dataSelect
    ) {
        $this->quote = $quote;
        $this->quoteTemplates = $quoteTemplates;
        $this->margin = $margin;
        $this->company = $company;
        $this->dataSelect = $dataSelect;
    }

    public function quote(Quote $quote)
    {
        $this->authorize('view', $quote);

        $resource = $this->quote->find($quote);

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
            $this->quote->storeState($request)
        );
    }

    public function setVersion(SetVersionRequest $request, Quote $quote)
    {
        $this->authorize('update', $quote);

        return response()->json(
            $this->quote->setVersion($request->version_id, $quote)
        );
    }

    public function step1()
    {
        return response()->json(
            [
                'companies' => $this->company->allWithVendorsAndCountries(),
                'data_select_separators' => $this->dataSelect->all(),
                'supported_file_types' => Setting::get('supported_file_types_ui')
            ]
        );
    }

    public function step2(MappingReviewRequest $request)
    {
        $this->authorize('view', $this->quote->find($request->quote_id));

        if ($request->has('search')) {
            return response()->json(
                $this->quote->rows($request->quote_id, $request->search, $request->group_id)
            );
        }

        return response()->json(
            $this->quote->step2($request)
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
            $this->quote->rowsGroups($quote->id)
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
            $this->margin->data()
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
            $this->quote->discounts($quote->id)
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
            $this->quote->tryDiscounts($request, $quote->id)
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
            $this->quote->review($quote->id)
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
            $this->quote->findGroupDescription($group, $quote->id)
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
            $this->quote->createGroupDescription($request, $quote->id)
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
            $this->quote->updateGroupDescription($request, $group, $quote->id)
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
            $this->quote->moveGroupDescriptionRows($request, $quote->id)
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
            $this->quote->deleteGroupDescription($group, $quote->id)
        );
    }
}
