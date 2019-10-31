<?php namespace App\Models\Quote\Discount;

use App\Contracts\ActivatableInterface;
use App\Models \ {
    UuidModel,
    Quote\Quote
};
use App\Traits \ {
    Activatable,
    BelongsToCountry,
    BelongsToUser,
    BelongsToVendor,
    Search\Searchable,
    Collaboration\BelongsToCollaboration
};
use App\Models\Quote\Discount as QuoteDiscount;
use Arr;

abstract class Discount extends UuidModel implements ActivatableInterface
{
    use Activatable, Searchable, BelongsToCountry, BelongsToVendor, BelongsToUser, BelongsToCollaboration;

    protected $perPage = 8;

    protected static function boot()
    {
        parent::boot();

        /**
         * Create Pivot model instance for Polymorphic relations on Quotes
         */
        static::creating(function (Discount $model) {
            $model->quoteDiscount()->create([]);
        });
    }

    public function getFillable()
    {
        $fillable = [
            'country_id', 'vendor_id', 'name'
        ];

        return array_merge($this->fillable, array_diff($fillable, $this->guarded));
    }

    public function toSearchArray()
    {
        $this->load('country', 'vendor');

        return Arr::except($this->toArray(), ['vendor.logo']);
    }

    public function quoteDiscount()
    {
        return $this->morphOne(QuoteDiscount::class, 'discountable');
    }

    public function scopeQuoteAcceptable($query, Quote $quote)
    {
        return $query->whereHas('country', function ($query) use ($quote) {
                $query->whereId($quote->country_id);
            })
            ->whereHas('vendor', function ($query) use ($quote) {
                $query->whereId($quote->vendor_id);
            });
    }
}
