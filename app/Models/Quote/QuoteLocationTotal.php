<?php

namespace App\Models\Quote;

use App\Models\Data\Country;
use App\Models\Location;
use App\Traits\{Uuid,};
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property mixed location_id
 * @property mixed country_id
 * @property mixed location_coordinates
 * @property mixed|string|null location_address
 * @property mixed total_drafted_value
 * @property mixed total_drafted_count
 * @property mixed total_submitted_value
 * @property mixed total_submitted_count
 */
class QuoteLocationTotal extends Model
{
    use Uuid, SpatialTrait;

    protected $fillable = [
        'location_id', 'country_id', 'location_address', 'location_coordinates', 'total_drafted_count', 'total_drafted_value', 'total_submitted_count', 'total_submitted_value'
    ];

    protected $casts = [
        'total_drafted_count' => 'integer',
        'total_submitted_count' => 'integer',
        'total_drafted_value' => 'float',
        'total_submitted_value' => 'float',
    ];

    protected $spatialFields = [
        'location_coordinates'
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class)->withDefault();
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class)->withDefault();
    }

    public function getTotalCountAttribute(): int
    {
        return $this->total_drafted_count + $this->total_submitted_count;
    }

    public function getTotalValueAttribute(): float
    {
        return $this->total_drafted_value + $this->total_submitted_value;
    }

    public function getCountryCodeAttribute()
    {
        return $this->country->code;
    }
}
