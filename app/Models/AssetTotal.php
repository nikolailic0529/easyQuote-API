<?php

namespace App\Models;

use App\Traits\Uuid;
use App\Traits\BelongsToLocation;
use App\Traits\BelongsToVendor;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|null $location_id
 * @property string|null $country_id
 * @property string|null $user_id
 * @property string|null $location_coordinates
 * @property string|null $location_address
 * @property int|null $total_count
 * @property float|null $total_value
 */
class AssetTotal extends Model
{
    use Uuid, BelongsToVendor, BelongsToLocation, SpatialTrait;

    protected $fillable = [
        'location_id',
        'country_id',
        'user_id',
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

    public function assetCategory(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class)->withDefault();
    }
}
