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
     * Retrieve mapped imported rows.
     *
     * @param \App\Models\Quote\BaseQuote $quote
     * @param array|Closuse $criteria
     * @return mixed
     */
    public function retrieveRows(BaseQuote $quote, $criteria = []);

    /**
     * Find Rows by query.
     *
     * @param \App\Models\Quote\QuoteVersion|string $quote
     * @param string $query
     * @param string|null $groupId
     * @return \Illuminate\Support\Collection
     */
    public function searchRows($quote, string $query = '', ?string $groupId = null): Collection;

    /**
     * Calculate list price based on current mapping.
     *
     * @param \App\Models\Quote\BaseQuote $quote
     * @return float
     */
    public function calculateListPrice(BaseQuote $quote): float;

    /**
     * Calculate list price based on current mapping and selected rows & groups.
     *
     * @param \App\Models\Quote\BaseQuote $quote
     * @return float
     */
    public function calculateTotalPrice(BaseQuote $quote): float;

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
     * @param string|QuoteVersion $quote
     * @return Collection
     */
    public function retrieveRowsGroups($quote): Collection;

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
     * Mark as selected specific Rows Group Descriptions.
     *
     * @param array $ids
     * @param string $quote
     * @return boolean
     */
    public function selectGroupDescription(array $ids, string $quote): bool;

    /**
     * Delete specified Rows Group Description from specified Quote.
     *
     * @param string $id
     * @param string $quote_id
     * @return bool
     */
    public function deleteGroupDescription(string $id, string $quote_id): bool;

    /**
     * Create a new Quote Version if an authenticated user is not the initial Quote creator.
     *
     * @param Quote $quote
     * @return QuoteVersion
     */
    public function createNewVersionIfNonCreator(Quote $quote): QuoteVersion;

    /**
     * Replicate Discounts from Source Quote to Target Quote.
     *
     * @param string $sourceId
     * @param string $targetId
     * @return void
     */
    public function replicateDiscounts(string $sourceId, string $targetId): void;

    /**
     * Replicate Mapping from Source Quote to Target Quote.
     *
     * @param string $sourceId
     * @param string $targetId
     * @return void
     */
    public function replicateMapping(string $sourceId, string $targetId): void;

    /**
     * Get wildcard quote permission.
     *
     * @param Quote $quote
     * @param array $permissions
     * @return string
     */
    public function getQuotePermission(Quote $quote, array $permissions = ['*']): string;
}