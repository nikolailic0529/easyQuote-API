<?php

namespace App\Models\Quote;

use App\Traits\{
    Uuid,
    BelongsToCountry,
    BelongsToLocation,
};
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Illuminate\Database\Eloquent\Model;

class QuoteLocationTotal extends Model
{
    use Uuid, BelongsToLocation, BelongsToCountry, SpatialTrait;

    protected $fillable = [
        'location_id', 'country_id', 'user_id', 'location_address', 'location_coordinates', 'total_drafted_count', 'total_drafted_value', 'total_submitted_count', 'total_submitted_value'
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

    public function getTotalCountAttribute(): int
    {
        return $this->total_drafted_count + $this->total_submitted_count;
    }

    public function getTotalValueAttribute(): float
    {
        return $this->total_drafted_value + $this->total_submitted_value;
    }
}
