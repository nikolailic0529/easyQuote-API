<?php

namespace App\Models\QuoteTemplate;

use App\Contracts\ActivatableInterface;
use App\Models\BaseModel;
use App\Models\Data\Country;
use App\Traits \ {
    Activatable,
    BelongsToCompany,
    BelongsToCountries,
    BelongsToCurrency,
    BelongsToUser,
    BelongsToTemplateFields,
    BelongsToVendor,
    Draftable,
    Systemable,
    Search\Searchable,
    HasQuotes,
    Activity\LogsActivity,
    QuoteTemplate\HasDataHeaders,
    Auth\Multitenantable
};
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Str;

abstract class BaseQuoteTemplate extends BaseModel implements ActivatableInterface
{
    use Multitenantable,
        BelongsToUser,
        BelongsToTemplateFields,
        BelongsToCompany,
        BelongsToVendor,
        BelongsToCountries,
        BelongsToCurrency,
        HasQuotes,
        HasDataHeaders,
        Draftable,
        Activatable,
        Systemable,
        Searchable,
        SoftDeletes,
        LogsActivity;

    protected $fillable = [
        'name', 'company_id', 'vendor_id', 'form_data'
    ];

    protected $hidden = [
        'deleted_at'
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'form_data' => 'array'
    ];

    protected static $logAttributes = [
        'name', 'company.name', 'vendor.name', 'currency.symbol'
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    public function getForeignKey()
    {
        return Str::snake(Str::after(class_basename(self::class), 'Base')).'_'.$this->getKeyName();
    }

    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'country_quote_template')->orderBy('name');
    }

    public function isAttached()
    {
        return $this->quotes()->exists();
    }

    public function toSearchArray()
    {
        return [
            'name' => $this->name,
            'countries' => $this->load('countries')->countries->map->only('name'),
            'vendor' => $this->vendor->toSearchArray(),
            'company' => $this->company->toSearchArray(),
            'created_at' => (string) $this->created_at
        ];
    }

    public function getItemNameAttribute()
    {
        return $this->name;
    }
}