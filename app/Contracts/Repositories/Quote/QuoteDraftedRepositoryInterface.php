<?php namespace App\Contracts\Repositories\Quote;

use App\Builder\Pagination\Paginator;
use App\Models\Quote\Quote;
use Illuminate\Database\Eloquent\Builder;

interface QuoteDraftedRepositoryInterface
{

    /**
     * Get All Drafted Quotes.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all();

    /**
     * Search by Drafted Quotes.
     *
     * @param string $query
     * @return \App\Builder\Pagination\Paginator
     */
    public function search(string $query = ''): Paginator;

    /**
     * Get Drafted Quotes Query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function userQuery(): Builder;

    /**
     * Find Drafted Quote.
     *
     * @return \App\Models\Quote
     */
    public function find(string $id): Quote;

    /**
     * Delete Drafted Quote.
     *
     * @param string $id
     * @return void
     */
    public function delete(string $id);

    /**
     * Activate Drafted Quote.
     *
     * @param string $id
     * @return void
     */
    public function activate(string $id);

    /**
     * Deactivate Drafted Quote.
     *
     * @param string $id
     * @return void
     */
    public function deactivate(string $id);
}
