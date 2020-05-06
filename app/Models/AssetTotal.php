<?php

namespace App\Models;

use App\Traits\Uuid;
use App\Traits\BelongsToAssetCategory;
use App\Traits\BelongsToLocation;
use App\Traits\BelongsToVendor;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetTotal extends Model
{
    use Uuid, BelongsToVendor, BelongsToLocation, BelongsToAssetCategory, SpatialTrait;

    protected $fillable = [
        'location_id',
        'location_coordinates',
        'location_address',
        'total_value',
        'total_count'
    ];

    protected $casts = [
        'unit_price' => 'float',
    ];

    protected $spatialFields = [
        'location_coordinates'
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class)->withDefault();
    }
}
