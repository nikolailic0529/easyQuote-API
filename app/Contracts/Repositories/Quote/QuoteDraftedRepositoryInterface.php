<?php

namespace App\Contracts\Repositories\Quote;

use App\Http\Resources\QuoteRepository\QuoteDraftedRepositoryCollection;
use App\Models\Quote\Quote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as DatabaseBuilder;

interface QuoteDraftedRepositoryInterface
{

    /**
     * Get All Drafted Quotes.
     *
     * @return mixed
     */
    public function all();

    /**
     * Search by Drafted Quotes.
     *
     * @param string $query
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Get Drafted Quotes Query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function userQuery(): Builder;

    /**
     * Get NonEloquent Query Builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function dbQuery(): DatabaseBuilder;

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

    /**
     * Map Resource into QuoteRepositoryCollection.
     *
     * @param mixed $resource
     * @return \App\Http\Resources\QuoteRepository\QuoteDraftedRepositoryCollection
     */
    public function toCollection($resource): QuoteDraftedRepositoryCollection;
}
