<?php namespace App\Contracts\Repositories\Quote;

use App\Models\Quote\Quote;
use App\Builder\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

interface QuoteSubmittedRepositoryInterface
{

    /**
     * Get All Submitted Quotes.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all();

    /**
     * Search by Submitted Quotes.
     *
     * @param string $query
     * @return \App\Builder\Pagination\Paginator
     */
    public function search(string $query = ''): Paginator;

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
