<?php

namespace App\Models;

use App\Contracts\ProvidesIdForHumans;
use App\Contracts\SearchableEntity;
use App\Models\Data\Country;
use App\Models\Quote\WorldwideDistribution;
use App\Traits\{Activatable, Search\Searchable, Uuid};
use Carbon\Carbon;
use Database\Factories\AddressFactory;
use Fico7489\Laravel\EloquentJoin\Traits\EloquentJoin;
use Illuminate\Database\Eloquent\{Builder,
    Factories\HasFactory,
    Model,
    Relations\BelongsTo,
    Relations\BelongsToMany,
    Relations\HasMany,
    Relations\MorphToMany,
    SoftDeletes
};

/**
 * Class Address
 *
 * @property string|null $pl_reference
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
 * @property Carbon|null $validated_at
 *
 * @property Contact|null $contact
 * @property Country|null $country
 * @property Location $location
 * @property-read User|null $user
 * @property-read string $address_representation
 */
class Address extends Model implements SearchableEntity, ProvidesIdForHumans
{
    use Uuid, SoftDeletes, Activatable, Searchable, EloquentJoin, HasFactory;

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

    protected $casts = [
        'validated_at' => 'datetime',
    ];

    protected static function newFactory(): AddressFactory
    {
        return AddressFactory::new();
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function distributorQuotes(): BelongsToMany
    {
        return $this->belongsToMany(WorldwideDistribution::class)->withPivot(['is_default', 'replicated_address_id']);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class)->withDefault();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function companies(): MorphToMany
    {
        return $this->morphedByMany(Company::class, 'addressable')->withPivot('is_default');
    }

    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('address_type', $type);
    }

    public function scopeCommonTypes(Builder $query): Builder
    {
        return $query->whereIn('address_type', __('address.types'));
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

    public function getCountryCodeAttribute()
    {
        return $this->country->code;
    }

    public function isEmpty(): bool
    {
        $attributes = ['address_1', 'address_2', 'city', 'state', 'post_code', 'country.iso_3166_2'];

        foreach ($attributes as $attribute) {
            if (filled(data_get($this, $attribute))) {
                return false;
            }
        }

        return true;
    }

    public function getIdForHumans(): string
    {
        return sprintf("%s %s %s", $this->address_1, $this->address_2, $this->city);
    }
}
