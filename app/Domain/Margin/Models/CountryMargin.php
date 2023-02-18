<?php

namespace App\Domain\Margin\Models;

use App\Domain\Activity\Concerns\LogsActivity;
use App\Domain\Country\Concerns\{BelongsToCountry};
use App\Domain\Rescue\Models\BaseQuote as Quote;
use App\Domain\Shared\Eloquent\Concerns\Searchable;
use App\Foundation\Support\Elasticsearch\Contracts\SearchableEntity;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property mixed      $vendor_id
 * @property mixed      $country_id
 * @property mixed|null $quote_type
 * @property mixed|null $is_fixed
 * @property mixed|null $value
 * @property mixed|null $method
 */
class CountryMargin extends Margin implements SearchableEntity
{
    use BelongsToCountry;
    use Searchable;
    use LogsActivity;

    protected $fillable = [
        'value', 'is_fixed', 'quote_type', 'method', 'country_id', 'vendor_id',
    ];

    protected static $logAttributes = [
        'value', 'is_fixed', 'quote_type', 'method', 'country.name', 'vendor.name',
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    public function scopeQuoteAcceptable(Builder $query, Quote $quote): Builder
    {
        return $query->whereVendorId($quote->vendor_id)
            ->whereCountryId($quote->country_id);
    }

    public function toSearchArray(): array
    {
        return [
            'value' => (string) $this->value,
            'vendor_name' => $this->vendor->name,
            'quote_type' => $this->quote_type,
            'country_name' => $this->country->name,
        ];
    }

    public function getFormattedValueAttribute()
    {
        return "{$this->value}%";
    }

    public function getItemNameAttribute()
    {
        return "Margin {$this->vendor->short_code} {$this->country->iso_3166_2} {$this->quote_type}";
    }
}
