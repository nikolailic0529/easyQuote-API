<?php

namespace App\Domain\Rescue\Contracts;

use App\Domain\Rescue\Models\QuoteVersion;
use App\Domain\Rescue\Resources\V1\DraftedCollection;
use Carbon\CarbonInterval;
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
     */
    public function cursor(?\Closure $scope = null): LazyCollection;

    /**
     * Check if any Quotes exist by the given RFQ Number.
     */
    public function rfqExist(string $rfqNumber, bool $activated = true): bool;

    /**
     * Search by Drafted Quotes.
     *
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Get Drafted Quotes Query.
     */
    public function userQuery(): Builder;

    /**
     * Retrieve the Drafted Quotes which are expiring based on the customer's valid until date.
     *
     * @param \App\Domain\User\Models\User|string|null $user
     */
    public function getExpiring(CarbonInterval $interval, $user = null, ?\Closure $scope = null): Collection;

    /**
     * Begin querying the expiring quotes.
     */
    public function expiringQuery(CarbonInterval $interval): Builder;

    /**
     * Count all expiring quotes.
     */
    public function countExpiring(CarbonInterval $interval, array $where = []): int;

    /**
     * Count all drafted quotes.
     */
    public function count(array $where = []): int;

    /**
     * Find Drafted Quote.
     */
    public function find(string $id): \App\Domain\Rescue\Models\Quote;

    /**
     * Find the specified Quote Version.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findVersion(string $id): QuoteVersion;

    /**
     * Delete Drafted Quote.
     *
     * @return void
     */
    public function delete(string $id);

    /**
     * Activate Drafted Quote.
     *
     * @return void
     */
    public function activate(string $id);

    /**
     * Deactivate Drafted Quote.
     *
     * @return void
     */
    public function deactivate(string $id);

    /**
     * Map Resource into QuoteRepositoryCollection.
     *
     * @param mixed $resource
     */
    public function toCollection($resource): DraftedCollection;

    /**
     * Delete the specified Quote Version.
     */
    public function deleteVersion(string $id): bool;
}
