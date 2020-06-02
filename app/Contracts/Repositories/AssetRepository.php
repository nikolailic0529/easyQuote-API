<?php

namespace App\Contracts\Repositories;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection as SupportCollection;
use App\DTO\AssetAggregate;

interface AssetRepository
{
    /**
     * Begin a new query for authenticated user.
     *
     * @return Builder
     */
    public function userQuery(): Builder;

    /**
     * Paginate existing assets.
     *
     * @return mixed
     */
    public function paginate();

    /**
     * Search existing assets.
     *
     * @param string $query
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Begin chunked query for assets.
     *
     * @param integer $count
     * @param callable $callback
     * @param array $with
     * @param callable|null $clause
     * @return boolean
     */
    public function chunk(int $count, callable $callback, array $with = [], ?callable $clause = null): bool;

    /**
     * Retrieve total_value & total_count by each user.
     *
     * @param string $locationId
     * @return SupportCollection[AssetAggregate]
     */
    public function aggregatesByUserAndLocation(string $locationId): SupportCollection;

    /**
     * Calculate total value by specific location.
     *
     * @param string $location
     * @return integer
     */
    public function sumByLocation(string $location): int;

    /**
     * Calculate total count by specific location.
     *
     * @param string $location
     * @return integer
     */
    public function countByLocation(string $location): int;

    /**
     * Count assets by specific clause.
     *
     * @param array $where
     * @return integer
     */
    public function count(array $where = []): int;

    /**
     * Check asset uniqueness by specific parameters.
     *
     * @param array $where
     * @return boolean
     */
    public function checkUniqueness(array $where): bool;

    /**
     * Get asset locations.
     *
     * @return Builder
     */
    public function locationsQuery(): Builder;

    /**
     * Find the specific asset by given id.
     *
     * @param string $id
     * @return Asset
     * @throws ModelNotFoundException
     */
    public function findOrFail(string $id): Asset;

    /**
     * Create a new asset with specific attributes.
     *
     * @param array $attributes
     * @return Asset
     */
    public function create(array $attributes): Asset;

    /**
     * Update the specific asset with given attributes.
     *
     * @param Asset|string $id
     * @param array $attributes
     * @return Asset
     */
    public function update($id, array $attributes): Asset;

    /**
     * Delete the specific asset.
     *
     * @param Asset|string $id
     * @return boolean
     */
    public function delete($id): bool;
}