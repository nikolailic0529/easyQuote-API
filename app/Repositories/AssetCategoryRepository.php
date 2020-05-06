<?php

namespace App\Repositories;

use App\Contracts\Repositories\AssetCategoryRepository as Contract;
use App\Models\AssetCategory;

class AssetCategoryRepository implements Contract
{
    protected const ASSET_CATEGORIES_CACHE_KEY = 'asset_categories';

    protected AssetCategory $assetCategory;

    public function __construct(AssetCategory $assetCategory)
    {
        $this->assetCategory = $assetCategory;    
    }

    public function all()
    {
        return $this->assetCategory->get(['id', 'name']);
    }

    public function allCached()
    {
        return cache()->sear(static::ASSET_CATEGORIES_CACHE_KEY.'.all', fn () => $this->all());
    }
}