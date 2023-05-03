<?php

namespace App\Domain\Stats\Models;

use App\Domain\Address\Models\Address;
use App\Domain\Company\Models\Company;
use App\Domain\Country\Models\Country;
use App\Domain\Location\Models\Location;
use App\Domain\Shared\Eloquent\Concerns\{Uuid};
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|null $customer_name
 * @property string|null $location_id
 * @property string|null $country_id
 * @property string|null $address_id
 * @property string|null $company_id
 * @property string|null $user_id
 * @property int|null    $total_count
 * @property float|null  $total_value
 * @property string|null $location_address
 * @property string|null $location_coordinates
 */
class CustomerTotal extends Model
{
    use Uuid;
    use SpatialTrait;

    protected $fillable = [
        'address_id', 'location_id', 'country_id', 'company_id', 'user_id', 'location_address', 'customer_name', 'total_value', 'total_count', 'location_coordinates',
    ];

    protected $spatialFields = [
        'location_coordinates',
    ];

    protected $casts = [
        'total_value' => 'float',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class)->withDefault();
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class)->withDefault();
    }

    public function getCountryCodeAttribute()
    {
        return $this->country->code;
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class)->withDefault();
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class)->withDefault();
    }
}
