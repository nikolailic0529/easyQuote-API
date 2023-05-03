<?php

namespace App\Domain\HpeContract\Models;

use App\Domain\Activity\Concerns\LogsActivity;
use App\Domain\Authentication\Concerns\Multitenantable;
use App\Domain\Company\Concerns\BelongsToCompany;
use App\Domain\Country\Concerns\BelongsToCountries;
use App\Domain\Country\Models\Country;
use App\Domain\Currency\Concerns\BelongsToCurrency;
use App\Domain\Shared\Eloquent\Concerns\Activatable;
use App\Domain\Shared\Eloquent\Concerns\Searchable;
use App\Domain\Shared\Eloquent\Concerns\Systemable;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\Shared\Eloquent\Contracts\ActivatableInterface;
use App\Domain\Template\Concerns\HasDataHeaders;
use App\Domain\User\Concerns\BelongsToUser;
use App\Domain\Vendor\Concerns\BelongsToVendor;
use App\Foundation\Support\Elasticsearch\Contracts\SearchableEntity;
use Fico7489\Laravel\EloquentJoin\Traits\EloquentJoin;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class HpeContractTemplate.
 *
 * @property string|null                                                                                              $name
 * @property \App\Domain\Vendor\Models\Vendor|\App\Foundation\Support\Elasticsearch\Contracts\SearchableEntity|null   $vendor
 * @property \App\Domain\Company\Models\Company|\App\Foundation\Support\Elasticsearch\Contracts\SearchableEntity|null $company
 * @property Collection<Country>                                                                                      $countries
 * @property Carbon|null                                                                                              $created_at
 */
class HpeContractTemplate extends Model implements ActivatableInterface, SearchableEntity
{
    use Uuid;
    use EloquentJoin;
    use Multitenantable;
    use BelongsToUser;
    use BelongsToCompany;
    use BelongsToVendor;
    use BelongsToCountries;
    use BelongsToCurrency;
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

    public function contracts(): HasMany
    {
        return $this->hasMany(HpeContract::class);
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
