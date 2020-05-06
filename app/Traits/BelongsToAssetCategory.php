<?php

namespace App\Traits;

use App\Models\AssetCategory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToAssetCategory
{
    public function assetCategory(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class)->withDefault();
    }
}