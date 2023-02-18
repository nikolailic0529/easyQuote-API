<?php

namespace App\Domain\Rescue\Contracts;

use App\Domain\Rescue\Resources\V1\SubmittedCollection;
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
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Get Submitted Quotes Query.
     */
    public function userQuery(): Builder;

    /**
     * Perform batch update.
     */
    public function batchUpdate(array $values, array $where = []): bool;

    /**
     * Iterate the existing submitted quotes using a cursor.
     *
     * @param \Closure $scope
     */
    public function cursor(?\Closure $scope = null): LazyCollection;

    /**
     * Count all submitted quotes.
     */
    public function count(array $where = []): int;

    /**
     * Find Submitted Quote.
     */
    public function find(string $id): \App\Domain\Rescue\Models\Quote;

    /**
     * Find Submitted Quote by RFQ number.
     *
     * @return \App\Models\BaseQuote
     */
    public function findByRFQ(string $rfq): \App\Domain\Rescue\Models\BaseQuote;

    /**
     * Submitted Price List by RFQ number.
     *
     * @return mixed
     */
    public function price(string $rfq);

    /**
     * Submitted Payment Schedule File by RFQ number.
     *
     * @return mixed
     */
    public function schedule(string $rfq);

    /**
     * Delete Submitted Quote.
     *
     * @return void
     */
    public function delete(string $id);

    /**
     * Activate Submitted Quote.
     *
     * @return void
     */
    public function activate(string $id);

    /**
     * Deactivate Submitted Quote.
     *
     * @return void
     */
    public function deactivate(string $id);

    /**
     * UnSubmit a specified Quote.
     */
    public function unSubmit(string $id): bool;

    /**
     * Set the specified Contract Template for the given Quote.
     */
    public function setContractTemplate(string $id, string $templateId): bool;

    /**
     * Map Resource to UserRepositoryCollection.
     *
     * @param mixed $resource
     */
    public function toCollection($resource): SubmittedCollection;
}
