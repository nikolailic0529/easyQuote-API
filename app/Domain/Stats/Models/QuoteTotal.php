<?php

namespace App\Domain\Stats\Models;

use App\Domain\Country\Models\Country;
use App\Domain\Location\Models\Location;
use App\Domain\Rescue\Models\Customer;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\User\Models\User;
use App\Domain\Shared\Eloquent\Concerns\{Uuid};
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string|null $quote_id
 * @property string|null $customer_id
 * @property string|null $company_id
 * @property string|null $location_id
 * @property string|null $user_id
 * @property string|null $location_address
 * @property mixed|null  $location_coordinates
 * @property float|null  $total_price
 * @property string|null $customer_name
 * @property string|null $rfq_number
 * @property string|null $quote_created_at
 * @property string|null $quote_submitted_at
 * @property string|null $valid_until_date
 * @property int|null    $quote_status
 */
class QuoteTotal extends Model
{
    use Uuid;
    use SpatialTrait;

    protected $guarded = [];

    protected $dates = [
        'quote_created_at', 'quote_submitted_at', 'valid_until_date',
    ];

    protected $spatialFields = [
        'location_coordinates',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function salesUnit(): BelongsTo
    {
        return $this->belongsTo(SalesUnit::class);
    }

    public function quote(): MorphTo
    {
        return $this->morphTo();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withDefault()->withTrashed();
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class)->withDefault();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Company\Models\Company::class)->withDefault();
    }

    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class);
    }
}
