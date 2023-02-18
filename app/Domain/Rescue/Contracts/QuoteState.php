<?php

namespace App\Domain\Rescue\Contracts;

use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\Rescue\Models\BaseQuote;
use App\Domain\Rescue\Models\Quote;
use App\Domain\Rescue\Models\{QuoteVersion};
use App\Domain\Rescue\Requests\StoreQuoteStateRequest;
use App\Domain\Rescue\Requests\TryDiscountsRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface QuoteState
{
    /**
     * Store current state of the Quote.
     */
    public function storeState(StoreQuoteStateRequest $request): array;

    /**
     * Process quote file import.
     */
    public function processQuoteFileImport(Quote $quote,
                                           QuoteFile $quoteFile,
                                           ?int $importablePageNumber = null,
                                           ?string $dataSeparatorReference = null): mixed;

    /**
     * Guess quote mapping basis on the previous saved mapping.
     */
    public function guessQuoteMapping(Quote $quote): void;

    /**
     * Create a new Quote.
     */
    public function create(array $attributes): Quote;

    /**
     * Get User's Quotes Query.
     */
    public function userQuery(): Builder;

    /**
     * Find a specified Quote.
     *
     * @return \App\Domain\Rescue\Models\Quote
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function find(string $id);

    /**
     * Retrieve an using version for a specified Quote.
     *
     * @param \App\Domain\Rescue\Models\Quote|string $quote
     *
     * @return \App\Domain\Rescue\Models\Quote
     */
    public function findVersion($quote): BaseQuote;

    /**
     * Set a specified Version for a specified Quote.
     *
     * @param \App\Domain\Rescue\Models\Quote|string $quote
     */
    public function setVersion(string $version_id, $quote): bool;

    /**
     * Get acceptable Discounts for the specified Quote.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function discounts(string $quoteId);

    /**
     * Return passed Discounts in the Request with calculated Total Margin after each passed Discount.
     *
     * @param TryDiscountsRequest|array              $attributes
     * @param \App\Domain\Rescue\Models\Quote|string $quote
     */
    public function tryDiscounts($attributes, $quote, bool $group = true): Collection;

    /**
     * Review Quote pages.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function review(string $quoteId);

    /**
     * Retrieve Groups of Imported Rows.
     */
    public function retrieveRowsGroups(BaseQuote $quote): Collection;

    /**
     * Retrieve specified Rows Group from specified Quote.
     */
    public function findGroupDescription(string $id, BaseQuote $quote): Collection;

    /**
     * Create Rows Group Description for specified Quote.
     *
     * @return Collection
     */
    public function createGroupDescription(array $attributes, Quote $quote);

    /**
     * Update specified Rows Group Description for specified Quote.
     */
    public function updateGroupDescription(string $id, Quote $quote, array $attributes): bool;

    /**
     * Move specified Rows to specified Rows Group Description for specified Quote.
     */
    public function moveGroupDescriptionRows(Quote $quote, array $attributes): bool;

    /**
     * Mark as selected specific Rows Group Descriptions.
     */
    public function selectGroupDescription(array $ids, Quote $quote): bool;

    /**
     * Delete specified Rows Group Description from specified Quote.
     */
    public function deleteGroupDescription(string $id, Quote $quote): bool;

    /**
     * Create a new Quote Version if an authenticated user is not the initial Quote creator.
     */
    public function createNewVersionIfNonCreator(Quote $quote): BaseQuote;

    /**
     * Replicate Discounts from Source Quote to Target Quote.
     *
     * @param \App\Domain\Rescue\Models\QuoteVersion $target
     */
    public function replicateDiscounts(BaseQuote $source, QuoteVersion $target): void;

    /**
     * Replicate Mapping from Source Quote to Target Quote.
     *
     * @param \App\Domain\Rescue\Models\QuoteVersion $target
     */
    public function replicateMapping(BaseQuote $source, QuoteVersion $target): void;

    /**
     * Replicate an entire quote model.
     */
    public function replicateQuote(Quote $quote): Quote;

    /**
     * Get wildcard quote permission.
     */
    public function getQuotePermission(Quote $quote, array $permissions = ['*']): string;

    /**
     * Process quote unravel.
     */
    public function processQuoteUnravel(Quote $quote): void;
}
