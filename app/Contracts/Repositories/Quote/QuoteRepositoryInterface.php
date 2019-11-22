<?php

namespace App\Contracts\Repositories\Quote;

use App\Models\Quote\Quote;
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
     * Find Collaboration Quote.
     *
     * @param Quote $quote
     * @return \App\Models\Quote\Quote
     */
    public function find(string $id);

    /**
     * Find Collaboration Quote with Calculated Total Price.
     *
     * @param string $id
     * @return \App\Models\Quote\Quote
     */
    public function preparedQuote(string $id);

    /**
     * Set/Create Margin for Quote.
     *
     * @param Quote $quote
     * @param array|null $attributes
     * @return \App\Models\Quote\Margin\CountryMargin
     */
    public function setMargin(Quote $quote, ?array $attributes);

    /**
     * Set Discounts for Quote.
     *
     * @param Quote $quote
     * @param array|null $attributes
     * @param boolean|null $detach
     * @return Quote
     */
    public function setDiscounts(Quote $quote, $attributes, $detach);

    /**
     * Fresh Attributes already attached Discounts.
     *
     * @param Quote $quote
     * @return void
     */
    public function freshDiscounts(Quote $quote): void;

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
     * Draft or Submit specified Quote.
     *
     * @param Collection $state
     * @param Quote $quote
     * @return void
     */
    public function draftOrSubmit(Collection $state, Quote $quote): void;

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
     * Hide specified Fields in Quote Review screen and generated PDF.
     *
     * @param Collection $state
     * @param Quote $quote
     * @return void
     */
    public function hideFields(Collection $state, Quote $quote): void;
}
