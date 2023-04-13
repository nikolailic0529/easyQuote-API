<?php

namespace App\Domain\Country\Models;

use App\Domain\Activity\Concerns\LogsActivity;
use App\Domain\Authentication\Concerns\Multitenantable;
use App\Domain\Currency\Models\Currency;
use App\Domain\Shared\Eloquent\Concerns\Activatable;
use App\Domain\Shared\Eloquent\Concerns\Searchable;
use App\Domain\Shared\Eloquent\Concerns\Systemable;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\Shared\Eloquent\Contracts\{HasOrderedScope};
use App\Domain\Shared\Eloquent\Contracts\ActivatableInterface;
use App\Foundation\Support\Elasticsearch\Contracts\SearchableEntity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * Class Country.
 *
 * @property string|null $user_id
 * @property string|null $iso_3166_2
 * @property string|null $name
 * @property string|null $default_currency_id
 * @property string|null $currency_code
 * @property string|null $currency_name
 * @property string|null $currency_symbol
 * @property string|null $flag
 */
class Country extends Model implements HasOrderedScope, ActivatableInterface, SearchableEntity
{
    use Uuid;
    use Multitenantable;
    use Activatable;
    use Systemable;
    use Searchable;
    use LogsActivity;
    use SoftDeletes;
    use HasRelationships;

    const FLAGS_DIRECTORY = 'img/countries';

    protected $fillable = [
        'iso_3166_2', 'name', 'default_currency_id', 'currency_code', 'currency_name', 'currency_symbol', 'user_id',
    ];

    protected $hidden = [
        'pivot', 'iso_3166_3', 'full_name', 'country_code', 'capital', 'citizenship', 'calling_code', 'laravel_through_key', 'default_country_id',
    ];

    protected static $logAttributes = [
        'name', 'iso_3166_2', 'currency_code', 'currency_name', 'currency_symbol', 'defaultCurrency.code',
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    public function defaultCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'default_currency_id')->withDefault();
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('name', 'asc');
    }

    public function scopeCode(Builder $query, string $code): Builder
    {
        return $query->where('iso_3166_2', $code);
    }

    public function getCodeAttribute()
    {
        return $this->iso_3166_2;
    }

    public function getFlagAttribute($flag)
    {
        return !is_null($flag)
            ? asset(static::FLAGS_DIRECTORY.'/'.$flag)
            : null;
    }

    public function getItemNameAttribute()
    {
        return $this->name;
    }

    public function toSearchArray(): array
    {
        return [
            'name' => $this->name,
            'iso_3166_2' => $this->iso_3166_2,
            'currency_code' => $this->currency_code,
            'currency_name' => $this->currency_name,
            'currency_symbol' => $this->currency_symbol,
            'created_at' => $this->created_at,
        ];
    }
}
