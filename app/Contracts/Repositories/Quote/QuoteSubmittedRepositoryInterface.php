<?php

namespace App\Contracts\Repositories\Quote;

use App\Http\Resources\V1\QuoteRepository\SubmittedCollection;
use App\Models\Quote\{BaseQuote, Quote};
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\LazyCollection;

interface QuoteSubmittedRepositoryInterface
{
    /**
     * Get All Submitted Quotes.
     *
     * @return mixed
     */
    public function all();

    /**
     * Search by Submitted Quotes.
     *
     * @param string $query
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Get Submitted Quotes Query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function userQuery(): Builder;

    /**
     * Perform batch update.
     *
     * @param array $values
     * @param array $where
     * @return boolean
     */
    public function batchUpdate(array $values, array $where = []): bool;

    /**
     * Iterate the existing submitted quotes using a cursor.
     *
     * @param \Closure $scope
     * @return \Illuminate\Support\LazyCollection
     */
    public function cursor(?Closure $scope = null): LazyCollection;

    /**
     * Count all submitted quotes.
     *
     * @param array $where
     * @return integer
     */
    public function count(array $where = []): int;

    /**
     * Find Submitted Quote.
     *
     * @return \App\Models\Quote\Quote
     */
    public function find(string $id): Quote;

    /**
     * Find Submitted Quote by RFQ number.
     *
     * @param string $rfq
     * @return \App\Models\BaseQuote
     */
    public function findByRFQ(string $rfq): BaseQuote;

    /**
     * Submitted Price List by RFQ number.
     *
     * @param string $rfq
     * @return mixed
     */
    public function price(string $rfq);

    /**
     * Submitted Payment Schedule File by RFQ number.
     *
     * @param string $rfq
     * @return mixed
     */
    public function schedule(string $rfq);

    /**
     * Delete Submitted Quote.
     *
     * @param string $id
     * @return void
     */
    public function delete(string $id);

    /**
     * Activate Submitted Quote.
     *
     * @param string $id
     * @return void
     */
    public function activate(string $id);

    /**
     * Deactivate Submitted Quote.
     *
     * @param string $id
     * @return void
     */
    public function deactivate(string $id);

    /**
     * UnSubmit a specified Quote.
     *
     * @param string $id
     * @return bool
     */
    public function unSubmit(string $id): bool;

    /**
     * Set the specified Contract Template for the given Quote.
     *
     * @param string $id
     * @param string $templateId
     * @return boolean
     */
    public function setContractTemplate(string $id, string $templateId): bool;

    /**
     * Map Resource to UserRepositoryCollection.
     *
     * @param mixed $resource
     * @return \App\Http\Resources\V1\QuoteRepository\SubmittedCollection
     */
    public function toCollection($resource): SubmittedCollection;
}
