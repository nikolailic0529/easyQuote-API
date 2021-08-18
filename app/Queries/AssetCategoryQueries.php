<?php

namespace App\Queries;

use App\Models\AssetCategory;
use Illuminate\Database\Eloquent\Builder;

class AssetCategoryQueries
{
    public function listOfAssetCategoriesQuery(): Builder
    {
        $model = new AssetCategory();

        return $model->newQuery()
            ->select([
                $model->getQualifiedKeyName(),
                $model->qualifyColumn('name')
            ]);
    }
}