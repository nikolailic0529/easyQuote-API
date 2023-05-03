<?php

namespace App\Domain\Worldwide\Models;

use App\Domain\BusinessDivision\Models\BusinessDivision;
use App\Domain\Company\Models\Company;
use App\Domain\ContractType\Models\ContractType;
use App\Domain\Country\Models\Country;
use App\Domain\Currency\Models\Currency;
use App\Domain\Template\Models\TemplateSchema;
use App\Domain\User\Models\User;
use App\Domain\Vendor\Models\Vendor;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Foundation\Support\Elasticsearch\Contracts\SearchableEntity;
use Database\Factories\SalesOrderTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class SalesOrderTemplate.
 *
 * @property string|null                                                                                                       $template_schema_id
 * @property string|null                                                                                                       $user_id
 * @property string|null                                                                                                       $business_division_id
 * @property string|null                                                                                                       $contract_type_id
 * @property string|null                                                                                                       $company_id
 * @property string|null                                                                                                       $vendor_id
 * @property string|null                                                                                                       $currency_id
 * @property string|null                                                                                                       $name
 * @property int|null                                                                                                          $is_system
 * @property \Illuminate\Support\Carbon|null                                                                                   $created_at
 * @property \Illuminate\Support\Carbon|null                                                                                   $updated_at
 * @property \Illuminate\Support\Carbon|null                                                                                   $deleted_at
 * @property string|null                                                                                                       $activated_at
 * @property \App\Domain\Template\Models\TemplateSchema|null                                                                   $templateSchema
 * @property \Illuminate\Database\Eloquent\Collection<\App\Domain\Country\Models\Country>|\App\Domain\Country\Models\Country[] $countries
 * @property \App\Domain\Vendor\Models\Vendor|null                                                                             $vendor
 * @property \App\Domain\Company\Models\Company|null                                                                           $company
 * @property \App\Domain\Currency\Models\Currency|null                                                                         $currency
 */
class SalesOrderTemplate extends Model implements SearchableEntity
{
    use Uuid;
    use SoftDeletes;
    use HasFactory;

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
            'created_at' => (string) $this->created_at,
        ];
    }

    public function getSearchIndex(): string
    {
        return $this->getTable();
    }
}
