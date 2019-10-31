<?php namespace App\Models\Quote\Margin;

use App\Traits \ {
    BelongsToCountry,
    Search\Searchable
};
use App\Models\Quote\Quote;
use Illuminate\Database\Eloquent\Builder;
use Arr;

class CountryMargin extends Margin
{
    use BelongsToCountry, Searchable;

    protected $fillable = [
        'value', 'is_fixed', 'quote_type', 'method', 'country_id', 'vendor_id'
    ];

    public function scopeQuoteAcceptable(Builder $query, Quote $quote): Builder
    {
        return $query->whereHas('vendor', function ($query) use ($quote) {
                return $query->whereId($quote->vendor_id);
            })
            ->whereHas('country', function ($query) use ($quote) {
                return $query->whereId($quote->country_id);
            });
    }

    public function toSearchArray()
    {
        $value = strval($this->value);
        $this->load('country', 'vendor');

        return array_merge(Arr::except($this->toArray(), ['vendor.logo']), compact('value'));
    }
}
