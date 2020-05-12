<?php

namespace App\Models;

use App\Traits\Uuid;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use Uuid, SoftDeletes, SpatialTrait;

    protected $fillable = [
        'place_id',
        'country_id',
        'searchable_address',
        'accuracy',
        'street_number',
        'locality',
        'formatted_address',
        'country_code',
        'coordinates',
        'route',
        'postal_town',
        'administrative_area_level_1',
        'administrative_area_level_2',
        'premise',
        'postal_code',
        'postal_code_suffix'
    ];

    protected $spatialFields = [
        'coordinates'
    ];
}
