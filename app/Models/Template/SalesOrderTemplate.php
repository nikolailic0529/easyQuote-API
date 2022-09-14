<?php

namespace App\Models\Template;

use App\Contracts\SearchableEntity;
use App\Models\BusinessDivision;
use App\Models\Company;
use App\Models\ContractType;
use App\Models\Data\Country;
use App\Models\Data\Currency;
use App\Models\User;
use App\Models\Vendor;
use App\Traits\Uuid;
use Database\Factories\SalesOrderTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class SalesOrderTemplate
 *
 * @property string|null $template_schema_id
 * @property string|null $user_id
 * @property string|null $business_division_id
 * @property string|null $contract_type_id
 * @property string|null $company_id
 * @property string|null $vendor_id
 * @property string|null $currency_id
 *
 * @property string|null $name
 * @property int|null $is_system
 *
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $activated_at
 *
 * @property-read \App\Models\Template\TemplateSchema|null $templateSchema
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Data\Country>|\App\Models\Data\Country[] $countries
 * @property-read \App\Models\Vendor|null $vendor
 * @property-read \App\Models\Company|null $company
 * @property-read Currency|null $currency
 */
class SalesOrderTemplate extends Model implements SearchableEntity
{
    use Uuid, SoftDeletes, HasFactory;

    protected $guarded = [];

    protected static function newFactory(): SalesOrderTemplateFactory
    {
        return SalesOrderTemplateFactory::new();
    }

    public function templateSchema(): BelongsTo
    {
        return $this->belongsTo(TemplateSchema::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function businessDivision(): BelongsTo
    {
        return $this->belongsTo(BusinessDivision::class);
    }

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function toSearchArray(): array
    {
        return [
            'name' => $this->name,
            'countries' => $this->countries->map->only('name'),
            'vendor' => $this->vendor->toSearchArray(),
            'company' => $this->company->toSearchArray(),
            'created_at' => (string)$this->created_at,
        ];
    }

    public function getSearchIndex(): string
    {
        return $this->getTable();
    }
}
