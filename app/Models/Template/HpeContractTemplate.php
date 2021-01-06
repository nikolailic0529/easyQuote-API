<?php

namespace App\Models\Template;

use App\Contracts\ActivatableInterface;
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
    Activity\LogsActivity,
    QuoteTemplate\HasDataHeaders,
    Auth\Multitenantable,
    HasHPEContracts,
};
use Fico7489\Laravel\EloquentJoin\Traits\EloquentJoin;
use Illuminate\Database\Eloquent\{
    Model,
    SoftDeletes,
    Relations\BelongsToMany,
};

class HpeContractTemplate extends Model implements ActivatableInterface
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
        HasHPEContracts,
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

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    public function getItemNameAttribute()
    {
        return "HPE Contract Template ({$this->name})";
    }

    public static function dataHeadersDictionary(): array
    {
        return __('template.hpe_contract_data_headers');
    }

    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'country_hpe_contract_template')->orderBy('name');
    }

    public function isAttached()
    {
        return $this->contracts()->exists();
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
