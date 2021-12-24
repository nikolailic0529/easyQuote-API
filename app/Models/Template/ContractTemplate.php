<?php

namespace App\Models\Template;

use App\Contracts\ActivatableInterface;
use App\Contracts\SearchableEntity;
use App\Models\Company;
use App\Models\Data\Country;
use App\Traits\{Activatable,
    Activity\LogsActivity,
    Auth\Multitenantable,
    BelongsToCompany,
    BelongsToCountries,
    BelongsToCurrency,
    BelongsToUser,
    BelongsToVendor,
    Draftable,
    HasContracts,
    QuoteTemplate\HasDataHeaders,
    Search\Searchable,
    Systemable,
    Uuid,
};
use App\Models\Vendor;
use Fico7489\Laravel\EloquentJoin\Traits\EloquentJoin;
use Illuminate\Database\Eloquent\{Collection, Model, Relations\BelongsToMany, SoftDeletes};
use Illuminate\Support\Carbon;

/**
 * Class ContractTemplate
 *
 * @property string|null $id
 * @property string|null $name
 * @property string|null $company_id
 * @property string|null $vendor_id
 * @property string|null $currency_id
 * @property bool|null $is_system
 * @property string|null $company_name
 * @property string|null $vendor_name
 * @property array|null $form_data
 * @property Collection<Country> $countries
 * @property string|null $activated_at
 * @property string|null $business_division_id
 * @property string|null $contract_type_id
 * @property Company|null $company
 * @property Vendor|null $vendor
 * @property Carbon|null $created_at
 */
class ContractTemplate extends Model implements ActivatableInterface, SearchableEntity
{
    use Uuid,
        EloquentJoin,
        Multitenantable,
        BelongsToUser,
        BelongsToCompany,
        BelongsToVendor,
        BelongsToCountries,
        BelongsToCurrency,
        HasContracts,
        HasDataHeaders,
        Draftable,
        Activatable,
        Systemable,
        Searchable,
        SoftDeletes,
        LogsActivity;

    protected $fillable = [
        'name', 'company_id', 'vendor_id', 'form_data',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'form_data' => 'array',
    ];

    protected static $logAttributes = [
        'name', 'company.name', 'vendor.name', 'currency.symbol',
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    public function getItemNameAttribute()
    {
        return "Contract Template ({$this->name})";
    }

    public static function dataHeadersDictionary(): array
    {
        return __('template.contract_data_headers');
    }

    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'country_contract_template')->orderBy('name');
    }

    public function isAttached()
    {
        return $this->contracts()->exists();
    }

    public function toSearchArray(): array
    {
        return [
            'name' => $this->name,
            'countries' => $this->loadMissing('countries')->countries->map->only('name'),
            'vendor' => $this->vendor->toSearchArray(),
            'company' => $this->company->toSearchArray(),
            'created_at' => (string)$this->created_at,
        ];
    }
}
