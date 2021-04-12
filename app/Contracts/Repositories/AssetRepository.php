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
}
