<?php

namespace App\Domain\Address\Models;

use App\Domain\Asset\Models\Asset;
use App\Domain\Company\Models\Company;
use App\Domain\Contact\Models\Contact;
use App\Domain\Country\Models\Country;
use App\Domain\Location\Models\Location;
use App\Domain\Shared\Eloquent\Concerns\{Activatable};
use App\Domain\Shared\Eloquent\Concerns\Searchable;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\Shared\Eloquent\Contracts\ProvidesIdForHumans;
use App\Domain\User\Models\User;
use App\Domain\Worldwide\Models\WorldwideDistribution;
use App\Foundation\Support\Elasticsearch\Contracts\SearchableEntity;
use Carbon\Carbon;
use Database\Factories\AddressFactory;
use Fico7489\Laravel\EloquentJoin\Traits\EloquentJoin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Address.
 *
 * @property string|null                       $pl_reference
 * @property string|null                       $location_id
 * @property string|null                       $address_type
 * @property string|null                       $address_1
 * @property string|null                       $address_2
 * @property string|null                       $city
 * @property string|null                       $state
 * @property string|null                       $state_code
 * @property string|null                       $post_code
 * @property string|null                       $contact_name
 * @property string|null                       $contact_number
 * @property string|null                       $contact_email
 * @property string|null                       $country_id
 * @property bool|null                         $is_default
 * @property Carbon|null                       $validated_at
 * @property Contact|null                      $contact
 * @property Country|null                      $country
 * @property Location                          $location
 * @property \App\Domain\User\Models\User|null $user
 * @property string                            $address_representation
 */
class Address extends Model implements SearchableEntity, ProvidesIdForHumans
{
    use Uuid;
    use SoftDeletes;
    use Activatable;
    use Searchable;
    use EloquentJoin;
    use HasFactory;

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
        return $this->belongsTo(\App\Domain\Contact\Models\Contact::class);
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
        return $this->belongsTo(\App\Domain\Location\Models\Location::class)->withDefault();
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
        return sprintf('AddressType=`%s` Address1=`%s` Address2=`%s` City=`%s` State=`%s` PostalCode=`%s` Country=`%s` ContactName=`%s` Phone=`%s` Email=`%s`',
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
        return sprintf('%s %s %s', $this->address_1, $this->address_2, $this->city);
    }
}
