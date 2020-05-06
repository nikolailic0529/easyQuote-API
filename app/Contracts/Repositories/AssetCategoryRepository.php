<?php

namespace App\Contracts\Repositories;

interface AssetCategoryRepository
{
    /**
     * Retrieve all asset categories.
     *
     * @return mixed
     */
    public function all();

    /**
     * Retrieve all asset categories from cache.
     *
     * @return mixed
     */
    public function allCached();
}