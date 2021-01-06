<?php

namespace App\Contracts\Services;

use App\Models\Quote\{
    Quote, QuoteVersion, BaseQuote
};
use App\Http\Requests\Quote\{
    StoreQuoteStateRequest,
    MoveGroupDescriptionRowsRequest,
    StoreGroupDescriptionRequest,
    UpdateGroupDescriptionRequest,
    TryDiscountsRequest
};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

interface QuoteState
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
     * Get User's Quotes Query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function userQuery(): Builder;

    /**
     * Find a specified Quote.
     *
     * @param string $id
     * @return \App\Models\Quote\Quote
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function find(string $id);

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
     * Retrieve Groups of Imported Rows.
     *
     * @param  BaseQuote $quote
     * @return Collection
     */
    public function retrieveRowsGroups(BaseQuote $quote): Collection;

    /**
     * Retrieve specified Rows Group from specified Quote.
     *
     * @param string $id
     * @param BaseQuote $quote
     * @return Collection
     */
    public function findGroupDescription(string $id, BaseQuote $quote): Collection;

    /**
     * Create Rows Group Description for specified Quote.
     *
     * @param array $attributes
     * @param Quote $quote
     * @return Collection
     */
    public function createGroupDescription(array $attributes, Quote $quote);

    /**
     * Update specified Rows Group Description for specified Quote.
     *
     * @param string $id
     * @param Quote $quote
     * @param array $attributes
     * @return bool
     */
    public function updateGroupDescription(string $id, Quote $quote, array $attributes): bool;

    /**
     * Move specified Rows to specified Rows Group Description for specified Quote.
     *
     * @param Quote $quote
     * @param array $attributes
     * @return bool
     */
    public function moveGroupDescriptionRows(Quote $quote, array $attributes): bool;

    /**
     * Mark as selected specific Rows Group Descriptions.
     *
     * @param  array $ids
     * @param  Quote $quote
     * @return boolean
     */
    public function selectGroupDescription(array $ids, Quote $quote): bool;

    /**
     * Delete specified Rows Group Description from specified Quote.
     *
     * @param  string $id
     * @param  Quote $quote
     * @return bool
     */
    public function deleteGroupDescription(string $id, Quote $quote): bool;

    /**
     * Create a new Quote Version if an authenticated user is not the initial Quote creator.
     *
     * @param Quote $quote
     * @return BaseQuote
     */
    public function createNewVersionIfNonCreator(Quote $quote): BaseQuote;

    /**
     * Replicate Discounts from Source Quote to Target Quote.
     *
     * @param BaseQuote $source
     * @param QuoteVersion $target
     * @return void
     */
    public function replicateDiscounts(BaseQuote $source, QuoteVersion $target): void;

    /**
     * Replicate Mapping from Source Quote to Target Quote.
     *
     * @param BaseQuote $source
     * @param QuoteVersion $target
     * @return void
     */
    public function replicateMapping(BaseQuote $source, QuoteVersion $target): void;

    /**
     * Replicate an entire quote model.
     *
     * @param Quote $quote
     * @return Quote
     */
    public function replicateQuote(Quote $quote): Quote;

    /**
     * Get wildcard quote permission.
     *
     * @param Quote $quote
     * @param array $permissions
     * @return string
     */
    public function getQuotePermission(Quote $quote, array $permissions = ['*']): string;
}
