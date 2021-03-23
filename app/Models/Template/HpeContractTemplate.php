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
    HasHPEContracts,
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
 * Class HpeContractTemplate
 *
 * @property string|null $name
 * @property Vendor|SearchableEntity|null $vendor
 * @property Company|SearchableEntity|null $company
 * @property Collection<Country> $countries
 * @property Carbon|null $created_at
 */
class HpeContractTemplate extends Model implements ActivatableInterface, SearchableEntity
{
    use Uuid,
        EloquentJoin,
        Multitenantable,
        BelongsToUser,
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
        'name', 'company_id', 'vendor_id', 'form_data',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'form_data' => 'array',
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

    public function toSearchArray(): array
    {
        return [
            'name' => $this->name,
            'countries' => $this->countries->pluck('name'),
            'vendor' => $this->vendor->toSearchArray(),
            'company' => $this->company->toSearchArray(),
            'created_at' => (string)$this->created_at,
        ];
    }
}
