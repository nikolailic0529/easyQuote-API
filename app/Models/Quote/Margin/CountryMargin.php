<?php

namespace App\Models\Quote\Margin;

use App\Traits\{
    BelongsToCountry,
    Search\Searchable,
    Activity\LogsActivity
};
use App\Models\Quote\Quote;
use Illuminate\Database\Eloquent\Builder;
use Arr;

class CountryMargin extends Margin
{
    use BelongsToCountry, Searchable, LogsActivity;

    protected $fillable = [
        'value', 'is_fixed', 'quote_type', 'method', 'country_id', 'vendor_id'
    ];

    protected static $logAttributes = [
        'value', 'is_fixed', 'quote_type', 'method', 'country.name', 'vendor.name'
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    public function scopeQuoteAcceptable(Builder $query, Quote $quote): Builder
    {
        return $query->whereVendorId($quote->vendor_id)
            ->whereCountryId($quote->country_id);
    }

    public function toSearchArray()
    {
        $value = strval($this->value);
        $this->load('country', 'vendor');

        return array_merge(Arr::except($this->toArray(), ['vendor.logo']), compact('value'));
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
