<?php

namespace App\Domain\Stats\Models;

use App\Domain\Asset\Models\Asset;
use App\Domain\Asset\Models\AssetCategory;
use App\Domain\Location\Concerns\BelongsToLocation;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\Vendor\Concerns\BelongsToVendor;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|null $location_id
 * @property string|null $country_id
 * @property string|null $user_id
 * @property string|null $location_coordinates
 * @property string|null $location_address
 * @property int|null    $total_count
 * @property float|null  $total_value
 */
class AssetTotal extends Model
{
    use Uuid;
    use BelongsToVendor;
    use BelongsToLocation;
    use SpatialTrait;

    protected $fillable = [
        'location_id',
        'country_id',
        'user_id',
        'location_coordinates',
        'location_address',
        'total_value',
        'total_count',
    ];

    protected $casts = [
        'unit_price' => 'float',
    ];

    protected $spatialFields = [
        'location_coordinates',
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
