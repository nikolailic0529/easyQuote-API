<?php

namespace App\Domain\Rescue\Models;

use App\Domain\Activity\Concerns\LogsActivity;
use App\Domain\Authentication\Concerns\Multitenantable;
use App\Domain\Company\Concerns\BelongsToCompany;
use App\Domain\Company\Models\Company;
use App\Domain\Country\Concerns\BelongsToCountries;
use App\Domain\Country\Models\Country;
use App\Domain\Currency\Concerns\BelongsToCurrency;
use App\Domain\Rescue\Concerns\HasQuotes;
use App\Domain\Shared\Eloquent\Concerns\Activatable;
use App\Domain\Shared\Eloquent\Concerns\Searchable;
use App\Domain\Shared\Eloquent\Concerns\Systemable;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\Shared\Eloquent\Contracts\ActivatableInterface;
use App\Domain\Template\Concerns\HasDataHeaders;
use App\Domain\User\Concerns\BelongsToUser;
use App\Domain\Vendor\Models\Vendor;
use App\Foundation\Support\Elasticsearch\Contracts\SearchableEntity;
use Fico7489\Laravel\EloquentJoin\Traits\EloquentJoin;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class QuoteTemplate.
 *
 * @property string|null         $id
 * @property string|null         $name
 * @property string|null         $company_id
 * @property string|null         $vendor_id
 * @property string|null         $currency_id
 * @property bool|null           $is_system
 * @property string|null         $company_name
 * @property string|null         $vendor_name
 * @property array|null          $form_data
 * @property Collection<Country> $countries
 * @property string|null         $activated_at
 * @property string|null         $business_division_id
 * @property string|null         $contract_type_id
 * @property Company|null        $company
 * @property Vendor|null         $vendor
 * @property Collection<\App\Domain\Vendor\Models\Vendor> vendors
 * @property Carbon|null $created_at
 */
class QuoteTemplate extends Model implements ActivatableInterface, SearchableEntity
{
    use Uuid;
    use EloquentJoin;
    use Multitenantable;
    use BelongsToUser;
    use BelongsToCompany;
    use BelongsToCountries;
    use BelongsToCurrency;
    use HasQuotes;
    use HasDataHeaders;
    use Activatable;
    use Systemable;
    use Searchable;
    use SoftDeletes;
    use LogsActivity;

    protected $fillable = [
        'name', 'company_id', 'vendor_id', 'form_data',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'form_data' => 'array',
        'form_values_data' => 'array',
    ];

    protected static array $logAttributes = [
        'name', 'company.name', 'vendor.name', 'currency.symbol',
    ];

    protected static bool $logOnlyDirty = true;

    protected static bool $submitEmptyLogs = false;

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

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class)->withDefault();
    }

    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class);
    }

    public function toSearchArray(): array
    {
        return [
            'name' => $this->name,
            'countries' => $this->loadMissing('countries')->countries->map->only('name'),
            'vendor' => $this->vendor->toSearchArray(),
            'company' => $this->company->toSearchArray(),
            'created_at' => (string) $this->created_at,
        ];
    }

    public function markAsDrafted(): bool
    {
        return $this->forceFill(['drafted_at' => now()])->save();
    }

    public function markAsNotDrafted(): bool
    {
        return $this->forceFill(['drafted_at' => null])->save();
    }

    public function getDraftedAtAttribute($value)
    {
        return carbon_format($value, config('date.format_time'));
    }
}
