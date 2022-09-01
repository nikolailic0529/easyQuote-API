<?php

namespace App\Models\Customer;

use App\Models\Address;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Data\Country;
use App\Traits\{Activity\LogsActivity,
    BelongsToAddresses,
    BelongsToContacts,
    BelongsToCountry,
    BelongsToVendors,
    HasAddressTypes,
    HasContactTypes,
    Migratable,
    Quote\HasQuotes,
    Submittable,
    Uuid};
use App\Traits\Auth\Multitenantable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\{Collection, Model, Relations\BelongsTo, SoftDeletes};
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection as BaseCollection;
use Rennokki\QueryCache\Traits\QueryCacheable;
use Staudenmeir\EloquentHasManyDeep\{HasManyDeep, HasOneDeep, HasRelationships,};

/**
 * @property string|null $company_reference_id
 * @property string|null $user_id
 * @property string|null $country_id
 * @property string $name
 * @property CarbonInterface $support_start
 * @property CarbonInterface $support_end
 * @property CarbonInterface $valid_until
 * @property string $rfq
 * @property string $sequence_number
 * @property string $source
 * @property string $payment_terms
 * @property string $invoicing_terms
 * @property BaseCollection $service_levels
 * @property string $vat
 * @property string $email
 * @property string $phone
 * @property string $int_company_id
 * @property string|null $migrated_at
 *
 * @property-read Collection<Address>|Address[] $addresses
 * @property-read Collection<Contact>|Contact[] $contacts
 * @property-read Country $country
 */
class Customer extends Model
{
    const S4_SOURCE = 'S4', EQ_SOURCE = 'EQ';

    use Uuid,
        Multitenantable,
        BelongsToAddresses,
        HasAddressTypes,
        BelongsToContacts,
        BelongsToCountry,
        BelongsToVendors,
        HasContactTypes,
        Submittable,
        HasQuotes,
        SoftDeletes,
        LogsActivity,
        Migratable,
        HasRelationships,
        QueryCacheable;

    protected $fillable = [
        'name',
        'customer_name',
        'support_start',
        'support_end',
        'rfq',
        'sequence_number',
        'source',
        'valid_until',
        'payment_terms',
        'invoicing_terms',
        'service_levels',
        'vat',
        'email',
        'phone',
        'int_company_id'
    ];

    protected $hidden = [
        'updated_at', 'deleted_at'
    ];

    protected $casts = [
        'service_levels' => 'collection',
        'support_start' => 'date',
        'support_end' => 'date',
        'valid_until' => 'date',
    ];

    protected static $logAttributes = [
        'customer_name:name',
        'support_start_date:support_start',
        'support_end_date:support_end',
        'rfq_number:rfq',
        'quotation_valid_until:valid_until',
        'invoicing_terms',
        'service_levels:service_levels_formatted',
    ];

    protected static $recordEvents = ['deleted'];

    public function addresses(): MorphToMany
    {
        return $this->morphToMany(Address::class, 'addressable')->withTrashed();
    }

    public function locations(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->addresses(), (new Address)->location());
    }

    public function equipmentLocation(): HasOneDeep
    {
        return $this->hasOneDeepFromRelations($this->addresses(), (new Address)->location())->where('addresses.address_type', 'Equipment')->withDefault();
    }

    public function getServiceLevelsFormattedAttribute(): string
    {
        return BaseCollection::wrap($this->service_levels)->pluck('service_level')->join(', ');
    }

    public function scopeNotInUse($query)
    {
        return $query->drafted()->doesntHave('quotes');
    }

    public function getItemNameAttribute(): string
    {
        return "Customer ($this->rfq)";
    }

    public function belongsToEasyQuote(): bool
    {
        return $this->source === static::EQ_SOURCE;
    }

    public function toSearchArray(): array
    {
        return [
            'name' => $this->name,
            'rfq' => $this->rfq,
            'valid_until' => $this->valid_until?->toDateString(),
            'support_start' => $this->support_start?->toDateString(),
            'support_end' => $this->support_end?->toDateString(),
        ];
    }

    public function referencedCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_reference_id');
    }
}
