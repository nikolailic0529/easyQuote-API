<?php

namespace App\Models;

use App\Contracts\{
    WithImage,
    ActivatableInterface,
    HasOrderedScope,
    WithLogo
};
use App\Models\{
    Data\Country,
    QuoteTemplate\QuoteTemplate,
};
use App\Traits\{
    Activatable,
    BelongsToAddresses,
    BelongsToContacts,
    BelongsToUser,
    BelongsToVendors,
    Image\HasImage,
    Image\HasLogo,
    Search\Searchable,
    Systemable,
    Quote\HasQuotes,
    QuoteTemplate\HasQuoteTemplates,
    Activity\LogsActivity,
    Auth\Multitenantable,
    Uuid
};
use Illuminate\Database\Eloquent\{
    Model,
    SoftDeletes,
};
use Illuminate\Database\Eloquent\{
    Relations\BelongsTo,
    Relations\BelongsToMany,
};
use Staudenmeir\EloquentHasManyDeep\{
    HasManyDeep,
    HasRelationships,
};

class Company extends Model implements WithImage, WithLogo, ActivatableInterface, HasOrderedScope
{
    public const TYPES = ['Internal', 'External'];

    public const CATEGORIES = ['End User', 'Reseller', 'Business Partner'];

    public const INT_TYPE = 'Internal';

    public const EXT_TYPE = 'External';

    public const REGULAR_RELATIONSHIPS = [
        'defaultCountry', 'defaultVendor', 'defaultTemplate', 'vendors', 'addresses.country', 'contacts', 'vendors.countries', 'addresses.country', 'contacts'
    ];

    use Uuid,
        Multitenantable,
        HasLogo,
        HasImage,
        BelongsToUser,
        BelongsToVendors,
        BelongsToAddresses,
        BelongsToContacts,
        Activatable,
        Searchable,
        Systemable,
        HasQuoteTemplates,
        HasQuotes,
        LogsActivity,
        SoftDeletes,
        HasRelationships;

    protected $fillable = [
        'name', 'short_code', 'category', 'vat', 'type', 'email', 'website', 'phone', 'default_vendor_id', 'default_country_id', 'default_template_id'
    ];

    protected static $logAttributes = [
        'name', 'category', 'vat', 'type', 'email', 'category', 'website', 'phone', 'defaultVendor.name', 'defaultCountry.name', 'defaultTemplate.name'
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class)->join('companies', 'companies.id', '=', 'company_vendor.company_id')
            ->orderByRaw("field(`vendors`.`id`, `companies`.`default_vendor_id`, null) desc");
    }

    public function locations(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->addresses(), (new Address)->location());
    }

    public function defaultVendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function defaultCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function defaultTemplate(): BelongsTo
    {
        return $this->belongsTo(QuoteTemplate::class, 'default_template_id');
    }

    public function sortVendorsCountries(): self
    {
        $vendors = $this->vendors->map(
            fn ($vendor) =>
            $vendor->setRelation(
                'countries',
                $vendor->countries->sortByDesc(fn ($country) => ($this->default_country_id === $country->id))->values()
            )
        );

        return $this->setRelation('vendors', $vendors);
    }

    public function scopeVendor($query, string $id)
    {
        return $query->whereHas('vendors', fn ($query) => $query->where('vendors.id', $id));
    }

    public function scopeOrdered($query)
    {
        return $query->orderByRaw("field(`vat`, ?, null) desc", [CP_DEF_VAT]);
    }

    public function inUse(): bool
    {
        return $this->quotes()->exists() || $this->quoteTemplates()->exists();
    }

    public function getItemNameAttribute()
    {
        return $this->name;
    }

    public function withAppends()
    {
        return $this->append('logo');
    }

    public function toSearchArray()
    {
        return [
            'name'          => $this->name,
            'vat'           => $this->vat,
            'type'          => $this->type,
            'email'         => $this->email,
            'phone'         => $this->phone,
            'created_at'    => $this->created_at
        ];
    }
}
