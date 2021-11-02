<?php

namespace App\Models;

use App\Contracts\SearchableEntity;
use App\Traits\{Activatable, BelongsToCountry, BelongsToLocation, Search\Searchable, Uuid};
use App\Models\Data\Country;
use Fico7489\Laravel\EloquentJoin\Traits\EloquentJoin;
use Illuminate\Database\Eloquent\{Builder, Model, Relations\BelongsTo, SoftDeletes};

/**
 * Class Address
 *
 * @property string|null $location_id
 * @property string|null $address_type
 * @property string|null $address_1
 * @property string|null $address_2
 * @property string|null $city
 * @property string|null $state
 * @property string|null $state_code
 * @property string|null $post_code
 * @property string|null $contact_name
 * @property string|null $contact_number
 * @property string|null $contact_email
 * @property string|null $country_id
 * @property bool|null $is_default
 *
 * @property Country|null $country
 * @property Location $location
 * @property-read string $address_representation
 */
class Address extends Model implements SearchableEntity
{
    use Uuid, SoftDeletes, Activatable, Searchable, EloquentJoin;

    public const TYPES = ['Invoice', 'Client', 'Machine', 'Equipment', 'Hardware', 'Software'];

    protected $fillable = [
        'address_type',
        'address_1',
        'address_2',
        'city',
        'state',
        'state_code',
        'post_code',
        'contact_name',
        'contact_number',
        'contact_email',
        'country_id',
    ];

    protected $hidden = [
        'addressable_id', 'addressable_type', 'deleted_at', 'pivot',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function getCountryCodeAttribute()
    {
        return $this->country->code;
    }

    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('address_type', $type);
    }

    public function scopeCommonTypes(Builder $query): Builder
    {
        return $query->whereIn('address_type', __('address.types'));
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class)->withDefault();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toSearchArray(): array
    {
        return [
            'city' => $this->city,
            'country_name' => $this->country?->name,
            'state' => $this->state,
            'address_type' => $this->address_type,
            'address_1' => $this->address_1,
            'address_2' => $this->address_2,
            'state_code' => $this->state_code,
            'post_code' => $this->post_code,
            'created_at' => $this->created_at,
        ];
    }

    public function getAddressRepresentationAttribute(): string
    {
        return sprintf("AddressType=`%s` Address1=`%s` Address2=`%s` City=`%s` State=`%s` PostalCode=`%s` Country=`%s` ContactName=`%s` Phone=`%s` Email=`%s`",
            $this->address_type,
            $this->address_1 ?? '',
            $this->address_2 ?? '',
            $this->city ?? '',
            $this->state ?? '',
            $this->post_code,
            $this->country->iso_3166_2 ?? '',
            $this->contact_phone ?? '',
            $this->contact_name ?? '',
            $this->contact_email ?? '',
        );
    }

}
