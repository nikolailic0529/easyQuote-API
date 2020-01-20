<?php

namespace App\Contracts\Repositories\Quote;

use App\Models\Quote\{
    BaseQuote,
    Quote
};
use App\Http\Resources\QuoteRepository\SubmittedCollection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as DatabaseBuilder;

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
    public function findByRfq(string $rfq): BaseQuote;

    /**
     * Find Submitted Quote Data by RFQ number.
     *
     * @param string $rfq
     * @param boolean $serviceCaused
     * @return \App\Models\Quote\BaseQuote
     */
    public function rfq(string $rfq, bool $serviceCaused = false): BaseQuote;

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
     * Submitted Generated PDF file by RFQ number.
     *
     * @param string $rfq
     * @return mixed
     */
    public function pdf(string $rfq);

    /**
     * Export a specified Quote as PDF.
     *
     * @param \App\Models\Quote|string $quote
     * @return void
     */
    public function exportPdf($quote);

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
     * Copy Submitted Quote with Relations
     *
     * @param string $id
     * @return void
     */
    public function copy(string $id);

    /**
     * Map Resource to UserRepositoryCollection.
     *
     * @param mixed $resource
     * @return \App\Http\Resources\QuoteRepository\SubmittedCollection
     */
    public function toCollection($resource): SubmittedCollection;
}
