<?php

namespace App\Models\Template;

use App\Contracts\ActivatableInterface;
use App\Contracts\ReindexQuery;
use App\Models\Data\Country;
use App\Traits\{
    Uuid,
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
    Auth\Multitenantable,
};
use Fico7489\Laravel\EloquentJoin\Traits\EloquentJoin;
use Illuminate\Database\Eloquent\{
    Builder,
    Model,
    SoftDeletes,
    Relations\BelongsToMany,
};

class QuoteTemplate extends Model implements ActivatableInterface
{
    use Uuid,
        EloquentJoin,
        Multitenantable,
        BelongsToUser,
        // BelongsToTemplateFields,
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
        'form_data' => 'array',
        'form_values_data' => 'array'
    ];

    protected static $logAttributes = [
        'name', 'company.name', 'vendor.name', 'currency.symbol'
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    public function getItemNameAttribute()
    {
        return "Quote Template ({$this->name})";
    }

    public static function dataHeadersDictionary(): array
    {
        return __('template.quote_data_headers');
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
            'countries' => $this->loadMissing('countries')->countries->map->only('name'),
            'vendor' => $this->vendor->toSearchArray(),
            'company' => $this->company->toSearchArray(),
            'created_at' => (string) $this->created_at
        ];
    }
}
