<?php

namespace App\Domain\Rescue\Models;

use App\Domain\Activity\Concerns\LogsActivity;
use App\Domain\Address\Concerns\BelongsToAddresses;
use App\Domain\Address\Concerns\HasAddressTypes;
use App\Domain\Address\Models\Address;
use App\Domain\Authentication\Concerns\Multitenantable;
use App\Domain\Company\Models\Company;
use App\Domain\Contact\Concerns\BelongsToContacts;
use App\Domain\Contact\Concerns\HasContactTypes;
use App\Domain\Contact\Models\Contact;
use App\Domain\Country\Concerns\BelongsToCountry;
use App\Domain\Country\Models\Country;
use App\Domain\Rescue\Quote\HasQuotes;
use App\Domain\Shared\Eloquent\Concerns\Submittable;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\Vendor\Concerns\BelongsToVendors;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection as BaseCollection;
use Rennokki\QueryCache\Traits\QueryCacheable;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasOneDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property string|null                                              $company_reference_id
 * @property string|null                                              $user_id
 * @property string|null                                              $country_id
 * @property string                                                   $name
 * @property CarbonInterface                                          $support_start
 * @property CarbonInterface                                          $support_end
 * @property CarbonInterface                                          $valid_until
 * @property string                                                   $rfq
 * @property string                                                   $sequence_number
 * @property string                                                   $source
 * @property string                                                   $payment_terms
 * @property string                                                   $invoicing_terms
 * @property BaseCollection                                           $service_levels
 * @property string                                                   $vat
 * @property string                                                   $email
 * @property string                                                   $phone
 * @property string                                                   $int_company_id
 * @property string|null                                              $migrated_at
 * @property Collection<Address>|Address[]                            $addresses
 * @property Collection<\App\Domain\Contact\Models\Contact>|Contact[] $contacts
 * @property Country                                                  $country
 */
class Customer extends Model
{
    use Uuid;

    use Multitenantable;

    use BelongsToAddresses;

    use HasAddressTypes;

    use BelongsToContacts;

    use BelongsToCountry;

    use BelongsToVendors;

    use HasContactTypes;

    use Submittable;

    use HasQuotes;

    use SoftDeletes;

    use LogsActivity;

    use HasRelationships;

    use QueryCacheable;
    const S4_SOURCE = 'S4';
    const EQ_SOURCE = 'EQ';

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
        'int_company_id',
    ];

    protected $hidden = [
        'updated_at', 'deleted_at',
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
        return $this->hasManyDeepFromRelations($this->addresses(), (new Address())->location());
    }

    public function equipmentLocation(): HasOneDeep
    {
        return $this->hasOneDeepFromRelations($this->addresses(), (new Address())->location())
            ->where('addresses.address_type', 'Equipment')
            ->withDefault();
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

    public function markAsMigrated(string $column = 'migrated_at'): void
    {
        static::whereKey($this->getKey())->limit(1)->update([$column => now()]);
    }

    public function markAsNotMigrated(string $column = 'migrated_at'): void
    {
        static::whereKey($this->getKey())->limit(1)->update([$column => null]);
    }

    public function scopeMigrated(Builder $query, string $column = 'migrated_at'): Builder
    {
        return $query->whereNotNull($column);
    }

    public function scopeNotMigrated(Builder $query, string $column = 'migrated_at'): Builder
    {
        return $query->whereNull($column);
    }
}
