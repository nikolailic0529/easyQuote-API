<?php

namespace App\Contracts\Repositories\Quote;

use App\Models\Quote\Quote;
use App\Builder\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

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
     * @return \App\Models\Quote
     */
    public function find(string $id): Quote;

    /**
     * Find Submitted Quote by RFQ number.
     *
     * @param string $rfq
     * @return array
     */
    public function findByRfq(string $rfq): Quote;

    /**
     * Find Submitted Quote Data by RFQ number.
     *
     * @param string $rfq
     * @return array
     */
    public function rfq(string $rfq): array;

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
     * Copy Submitted Quote with Relations
     *
     * @param string $id
     * @return void
     */
    public function copy(string $id);
}
