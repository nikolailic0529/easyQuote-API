<?php

namespace App\Models;

use App\Models\Data\Country;
use App\Traits\{Uuid,};
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string|null $formatted_address
 * @property mixed $coordinates
 * @property Country $country
 */
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

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class)->withDefault();
    }

    public function getCountryCodeAttribute(): ?string
    {
        return $this->country->iso_3166_2;
    }
}
