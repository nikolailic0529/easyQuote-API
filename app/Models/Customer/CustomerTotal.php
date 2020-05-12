<?php

namespace App\Models\Customer;

use App\Traits\{
    Uuid,
    BelongsToAddress,
    BelongsToCompany,
    BelongsToCountry,
    BelongsToLocation,
};
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Illuminate\Database\Eloquent\Model;

class CustomerTotal extends Model
{
    use Uuid, BelongsToCompany, BelongsToAddress, BelongsToLocation, BelongsToCountry, SpatialTrait;

    protected $fillable = [
        'address_id', 'location_id', 'country_id', 'company_id', 'location_address', 'customer_name', 'total_value', 'total_count', 'location_coordinates'
    ];

    protected $spatialFields = [
        'location_coordinates'
    ];

    protected $casts = [
        'total_value' => 'float'
    ];
}
