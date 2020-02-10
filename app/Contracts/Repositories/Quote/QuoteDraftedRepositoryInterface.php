<?php

namespace App\Contracts\Repositories\Quote;

use App\Http\Resources\QuoteRepository\DraftedCollection;
use App\Models\Quote\Quote;
use App\Models\Quote\QuoteVersion;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Closure;

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
     * Retrieve the Drafted Quotes which are expiring based on the customer's valid until date.
     *
     * @param \Carbon\CarbonInterval $interval
     * @param \App\Models\User|string|null $user
     * @param \Closure|null $scope
     * @return Collection
     */
    public function getExpiring(CarbonInterval $interval, $user = null, ?Closure $scope = null): Collection;

    /**
     * Find Drafted Quote.
     *
     * @return \App\Models\Quote\Quote
     */
    public function find(string $id): Quote;

    /**
     * Find the specified Quote Version.
     *
     * @param string $id
     * @return \App\Models\Quote\QuoteVersion
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findVersion(string $id): QuoteVersion;

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
     * @return \App\Http\Resources\QuoteRepository\DraftedCollection
     */
    public function toCollection($resource): DraftedCollection;

    /**
     * Delete the specified Quote Version.
     *
     * @param string $id
     * @return boolean
     */
    public function deleteVersion(string $id): bool;
}
