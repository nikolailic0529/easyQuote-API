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
     * Get User's Submitted Quote
     *
     * @return \App\Models\Quote
     */
    public function getSubmitted(string $id);

    /**
     * Get User's Drafted Quote
     *
     * @return \App\Models\Quote
     */
    public function getDrafted(string $id);

    /**
     * Get All User's Drafted Quotes
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function allDrafted();

    /**
     * Get All User's Submitted Quotes
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function allSubmitted();

    /**
     * Search by Drafted Quotes
     *
     * @param string $query
     * @return \App\Builder\Pagination\Paginator
     */
    public function searchDrafted(string $query = ''): Paginator;

    /**
     * Search by Submitted Quotes
     *
     * @param string $query
     * @return \App\Builder\Pagination\Paginator
     */
    public function searchSubmitted(string $query = ''): Paginator;

    /**
     * Get User's Drafted Quotes Query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function draftedQuery(): Builder;

    /**
     * Get User's Submitted Quotes Query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function submittedQuery(): Builder;

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
     * Get Rows Data after Applying Margin
     *
     * @param ReviewAppliedMarginRequest $request
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function step4(ReviewAppliedMarginRequest $request);

    /**
     * Delete User's Drafted Quote
     *
     * @param string $id
     * @return void
     */
    public function deleteDrafted(string $id);

    /**
     * Deactivate User's Drafted Quote
     *
     * @param string $id
     * @return void
     */
    public function deactivateDrafted(string $id);

    /**
     * Activate User's Drafted Quote
     *
     * @param string $id
     * @return void
     */
    public function activateDrafted(string $id);

    /**
     * Delete User's Submitted Quote
     *
     * @param string $id
     * @return void
     */
    public function deleteSubmitted(string $id);

    /**
     * Deactivate User's Submitted Quote
     *
     * @param string $id
     * @return void
     */
    public function deactivateSubmitted(string $id);

    /**
     * Activate User's Submitted Quote
     *
     * @param string $id
     * @return void
     */
    public function activateSubmitted(string $id);

    /**
     * Copy Submitted Quote with Relations
     *
     * @param string $id
     * @return void
     */
    public function copy(string $id);

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
}
