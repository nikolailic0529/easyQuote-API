<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Contracts\{
    WithImage,
    ActivatableInterface,
    HasOrderedScope,
    WithLogo
};
use App\Models\Data\Country;
use App\Models\QuoteTemplate\QuoteTemplate;
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
    Auth\Multitenantable
};
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends BaseModel implements WithImage, WithLogo, ActivatableInterface, HasOrderedScope
{
    use Multitenantable,
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
        SoftDeletes;

    protected $fillable = [
        'name', 'category', 'vat', 'type', 'email', 'website', 'phone', 'default_vendor_id', 'default_country_id', 'default_template_id'
    ];

    protected static $logAttributes = [
        'name', 'category', 'vat', 'type', 'email', 'category', 'website', 'phone', 'defaultVendor.name', 'defaultCountry.name', 'defaultTemplate.name'
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    protected $hidden = [
        'pivot',
        'created_at',
        'updated_at',
        'drafted_at',
        'deleted_at',
        'is_system',
        'image'
    ];

    public function vendors()
    {
        return $this->belongsToMany(Vendor::class)->join('companies', 'companies.id', '=', 'company_vendor.company_id')
            ->orderByRaw("field(`vendors`.`id`, `companies`.`default_vendor_id`, null) desc");
    }

    public function defaultVendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function defaultCountry()
    {
        return $this->belongsTo(Country::class);
    }

    public function defaultTemplate()
    {
        return $this->belongsTo(QuoteTemplate::class, 'default_template_id');
    }

    public function sortVendorsCountries(): self
    {
        $vendors = $this->vendors->map(function ($vendor) {
            $countries = $vendor->countries->sortByDesc(function ($country) {
                return $this->default_country_id === $country->id;
            })->values();

            return $vendor->setRelation('countries', $countries);
        });

        return $this->setRelation('vendors', $vendors);
    }

    public function scopeVendor($query, string $id)
    {
        return $query->whereHas('vendors', function ($query) use ($id) {
            $query->where('vendors.id', $id);
        });
    }

    public function scopeOrdered($query)
    {
        return $query->orderByRaw("field(`vat`, ?, null) desc", ['GB758501125']);
    }

    public function inUse()
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
}
