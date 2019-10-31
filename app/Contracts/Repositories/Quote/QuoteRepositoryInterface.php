<?php namespace App\Contracts\Repositories\Quote;

use App\Builder\Pagination\Paginator;
use App\Models\Quote\Quote;
use App\Http\Requests \ {
    StoreQuoteStateRequest,
    GetQuoteTemplatesRequest,
    MappingReviewRequest,
    ReviewAppliedMarginRequest
};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface QuoteRepositoryInterface
{
    /**
     * Store current state of the Quote
     *
     * @param StoreQuoteStateRequest $request
     * @return \App\Models\Quote\Quote
     */
    public function storeState(StoreQuoteStateRequest $request);

    /**
     * Return linked data Company->Vendor->Country->Language and Data Select Separators
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function step1();

    /**
     * Get Quote Templates by Company, Vendor, Country
     *
     * @param GetQuoteTemplatesRequest $request
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTemplates(GetQuoteTemplatesRequest $request);

    /**
     * Get Rows Data by Attached Columns
     *
     * @param MappingReviewRequest $request
     * @return \Illuminate\Support\Collection
     */
    public function step2(MappingReviewRequest $request);

    /**
     * Find User's Quote
     *
     * @param Quote $quote
     * @return \App\Models\Quote\Quote
     */
    public function find(string $id);

    /**
     * Find User's Quote with Modifications
     *
     * @param string $id
     * @return \App\Models\Quote\Quote
     */
    public function getWithModifications(string $id);

    /**
     * Set/Create Margin for Quote
     *
     * @param Quote $quote
     * @param array|null $attributes
     * @return \App\Models\Quote\Margin\CountryMargin
     */
    public function setMargin(Quote $quote, $attributes);

    /**
     * Set Discounts for Quote
     *
     * @param Quote $quote
     * @param array|null $attributes
     * @param boolean|null $detach
     * @return Quote
     */
    public function setDiscounts(Quote $quote, $attributes, $detach);

    /**
     * Get acceptable Discounts for the specified Quote
     *
     * @param string $quoteId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function discounts(string $quoteId);

    /**
     * Review Quote pages
     *
     * @param string $quoteId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function review(string $quoteId);

    /**
     * Retrieve Quote Mapping Review Data
     *
     * @param Quote $quote
     * @param bool|null $clearCache
     * @return void
     */
    public function mappingReviewData(Quote $quote, $clearCache = null);

    /**
     * Find Rows by query
     *
     * @param string $id
     * @param string $query
     * @return \Illuminate\Support\Collection
     */
    public function rows(string $id, string $query = ''): Collection;
}
