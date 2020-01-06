<?php

namespace App\Contracts\Repositories\Quote;

use App\Models\Quote\{
    Quote, QuoteVersion, BaseQuote
};
use App\Http\Requests\{
    StoreQuoteStateRequest,
    GetQuoteTemplatesRequest,
    MappingReviewRequest,
    Quote\MoveGroupDescriptionRowsRequest,
    Quote\StoreGroupDescriptionRequest,
    Quote\UpdateGroupDescriptionRequest
};
use App\Http\Requests\Quote\TryDiscountsRequest;
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
     * Create a new Quote.
     *
     * @param array $attributes
     * @return Quote
     */
    public function create(array $attributes): Quote;

    /**
     * Get Rows Data by Attached Columns.
     *
     * @param MappingReviewRequest $request
     * @return \Illuminate\Support\Collection
     */
    public function step2(MappingReviewRequest $request);

    /**
     * Get User's Quotes Query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function userQuery(): Builder;

    /**
     * Find a specified Quote.
     *
     * @param \App\Models\Quote\Quote|string $quote
     * @return \App\Models\Quote\Quote
     */
    public function find($quote);

    /**
     * Retrieve an using version for a specified Quote.
     *
     * @param \App\Models\Quote\Quote|string $quote
     * @return \App\Models\Quote\Quote
     */
    public function findVersion($quote): BaseQuote;

    /**
     * Set a specified Version for a specified Quote.
     *
     * @param string $version_id
     * @param \App\Models\Quote|string $quote
     * @return boolean
     */
    public function setVersion(string $version_id, $quote): bool;

    /**
     * Get acceptable Discounts for the specified Quote.
     *
     * @param string $quoteId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function discounts(string $quoteId);

    /**
     * Return passed Discounts in the Request with calculated Total Margin after each passed Discount.
     *
     * @param TryDiscountsRequest|array $attributes
     * @param Quote|string $quote
     * @param bool $group
     * @return Collection
     */
    public function tryDiscounts($attributes, $quote, bool $group = true): Collection;

    /**
     * Review Quote pages.
     *
     * @param string $quoteId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function review(string $quoteId);

    /**
     * Find Rows by query.
     *
     * @param string $id
     * @param string $query
     * @return \Illuminate\Support\Collection
     */
    public function rows(string $id, string $query = ''): Collection;

    /**
     * Retrieve Groups of Imported Rows.
     *
     * @param string $id
     * @return Collection
     */
    public function rowsGroups(string $id): Collection;

    /**
     * Retrieve specified Rows Group from specified Quote.
     *
     * @param string $id
     * @param string $quote_id
     * @return Collection
     */
    public function findGroupDescription(string $id, string $quote_id): Collection;

    /**
     * Create Rows Group Description for specified Quote.
     *
     * @param StoreGroupDescriptionRequest $request
     * @param string $quote_id
     * @return Collection
     */
    public function createGroupDescription(StoreGroupDescriptionRequest $request, string $quote_id): Collection;

    /**
     * Update specified Rows Group Description for specified Quote.
     *
     * @param UpdateGroupDescriptionRequest $request
     * @param string $id
     * @param string $quote_id
     * @return bool
     */
    public function updateGroupDescription(UpdateGroupDescriptionRequest $request, string $id, string $quote_id): bool;

    /**
     * Move specified Rows to specified Rows Group Description for specified Quote.
     *
     * @param MoveGroupDescriptionRowsRequest $request
     * @param string $quote_id
     * @return bool
     */
    public function moveGroupDescriptionRows(MoveGroupDescriptionRowsRequest $request, string $quote_id): bool;

    /**
     * Delete specified Rows Group Description from specified Quote.
     *
     * @param string $id
     * @param string $quote_id
     * @return bool
     */
    public function deleteGroupDescription(string $id, string $quote_id): bool;

    /**
     * Store Submittable Data for S4 and Submit Quote.
     *
     * @param Quote $quote
     * @return void
     */
    public function submit(Quote $quote): void;

    /**
     * Remove Stored Submittable Data and Mark Quote as Drafted.
     *
     * @param Quote $quote
     * @return void
     */
    public function draft(Quote $quote): void;

    /**
     * Create a new Quote Version if an authenticated user is not the initial Quote creator.
     *
     * @param Quote $quote
     * @return QuoteVersion
     */
    public function createNewVersionIfNonCreator(Quote $quote): QuoteVersion;
}
