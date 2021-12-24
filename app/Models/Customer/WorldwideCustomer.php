<?php

namespace App\Models\Customer;

use App\Contracts\SearchableEntity;
use App\Models\Address;
use App\Models\Data\Country;
use App\Models\Quote\WorldwideQuote;
use App\Traits\Search\Searchable;
use App\Traits\Uuid;
use Illuminate\Database\{Eloquent\Model,
    Eloquent\Relations\BelongsTo,
    Eloquent\Relations\HasMany,
    Eloquent\SoftDeletes};
use Illuminate\Support\Carbon;

/**
 * @property string $country_id
 * @property string $customer_name
 * @property string $rfq_number
 * @property string $source
 * @property \Illuminate\Support\Carbon $valid_until_date
 * @property \Illuminate\Support\Carbon $support_start_date
 * @property \Illuminate\Support\Carbon $support_end_date
 * @property string $invoicing_terms
 * @property array $service_levels
 * @property string $customer_vat
 * @property string $customer_email
 * @property string $customer_phone
 * @property string|null $hardware_address_id
 * @property string|null $software_address_id
 * @property Address|null $hardwareAddress
 * @property Address|null $softwareAddress
 * @property Country|null $country
 * @property Carbon|null $created_at
 */
class WorldwideCustomer extends Model implements SearchableEntity
{
    use Uuid, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'service_levels' => 'array',
    ];

    protected $dates = [
        'valid_until_date',
        'support_start_date',
        'support_end_date',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function worldwideQuotes(): HasMany
    {
        return $this->hasMany(WorldwideQuote::class);
    }

    public function hardwareAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function softwareAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function getSearchIndex(): string
    {
        return $this->getTable();
    }

    public function toSearchArray(): array
    {
        return [
            'country_name' => $this->country->name,
            'country_code' => $this->country->iso_3166_2,
            'customer_name' => $this->customer_name,
            'rfq_number' => $this->rfq_number,
            'valid_until_date' => optional($this->valid_until_date)->toDateString(),
            'support_start_date' => optional($this->support_start_date)->toDateString(),
            'support_end_date' => optional($this->support_end_date)->toDateString(),
            'created_at' => optional($this->created_at)->toDateString(),
        ];
    }
}
