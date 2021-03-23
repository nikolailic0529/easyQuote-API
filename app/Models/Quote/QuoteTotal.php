<?php

namespace App\Models\Quote;

use App\Models\Customer\Customer;
use App\Traits\{BelongsToCompany, BelongsToCountry, BelongsToLocation, BelongsToQuote, Uuid,};
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteTotal extends Model
{
    use Uuid, BelongsToQuote, BelongsToLocation, BelongsToCompany, BelongsToCountry, SpatialTrait;

    protected $fillable = ['quote_id', 'company_id', 'customer_id', 'location_id', 'country_id', 'user_id', 'location_coordinates', 'location_address', 'total_price', 'customer_name', 'rfq_number', 'valid_until_date', 'quote_created_at', 'quote_submitted_at'];

    protected $dates = [
        'quote_created_at', 'quote_submitted_at', 'valid_until_date'
    ];

    protected $spatialFields = [
        'location_coordinates'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withDefault()->withTrashed();
    }
}
