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
     * Check asset uniqueness by specific parameters.
     *
     * @param array $where
     * @return boolean
     */
    public function checkUniqueness(array $where): bool;

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
