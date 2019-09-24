<?php namespace App\Contracts\Repositories\Quote;

use App\Builder\Pagination\Paginator;
use App\Models\Quote\Quote;
use App\Http\Requests \ {
    StoreQuoteStateRequest,
    GetQuoteTemplatesRequest,
    MappingReviewRequest,
    ReviewAppliedMarginRequest
};

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
     * Get All User's Drafted Quotes
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDrafted();

    /**
     * Search by Drafted Quotes
     *
     * @param string $query
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function searchDrafted(string $query = ''): Paginator;

    /**
     * Find User's Quote
     *
     * @param Quote $quote
     * @return \App\Models\Quote\Quote
     */
    public function find(string $id);

    /**
     * Get Rows Data after Applying Margin
     *
     * @param ReviewAppliedMarginRequest $request
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function step4(ReviewAppliedMarginRequest $request);
}
