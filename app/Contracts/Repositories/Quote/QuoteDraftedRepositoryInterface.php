<?php

namespace App\Contracts\Repositories\Quote;

use App\Http\Resources\V1\QuoteRepository\DraftedCollection;
use App\Models\Quote\Quote;
use App\Models\Quote\QuoteVersion;
use Carbon\CarbonInterval;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\LazyCollection;

interface QuoteDraftedRepositoryInterface
{
    /**
     * Get All Drafted Quotes.
     *
     * @return mixed
     */
    public function all();

    /**
     * Iterate the existing drafted quotes using a cursor.
     *
     * @param \Closure $scope
     * @return \Illuminate\Support\LazyCollection
     */
    public function cursor(?Closure $scope = null): LazyCollection;

    /**
     * Check if any Quotes exist by the given RFQ Number.
     *
     * @param  string $rfqNumber
     * @param  bool $activated
     * @return boolean
     */
    public function rfqExist(string $rfqNumber, bool $activated = true): bool;

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
     * Begin querying the expiring quotes.
     *
     * @param \Carbon\CarbonInterval $interval
     * @return Builder
     */
    public function expiringQuery(CarbonInterval $interval): Builder;

    /**
     * Count all expiring quotes.
     *
     * @param CarbonInterval $interval
     * @param array $where
     * @return integer
     */
    public function countExpiring(CarbonInterval $interval, array $where = []): int;

    /**
     * Count all drafted quotes.
     *
     * @param array $where
     * @return integer
     */
    public function count(array $where = []): int;

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
     * @return \App\Http\Resources\V1\QuoteRepository\DraftedCollection
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
