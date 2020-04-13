<?php

namespace App\Models\Quote\Discount;

use App\Contracts\ActivatableInterface;
use App\Models\{
    Quote\BaseQuote,
    Quote\Discount as QuoteDiscount
};
use App\Traits\{
    Activatable,
    BelongsToCountry,
    BelongsToUser,
    BelongsToVendor,
    Search\Searchable,
    Auth\Multitenantable,
    Uuid
};
use Fico7489\Laravel\EloquentJoin\Traits\EloquentJoin;
use Illuminate\Database\Eloquent\{
    Model,
    SoftDeletes,
};
use Arr;

abstract class Discount extends Model implements ActivatableInterface
{
    use Uuid,
        Multitenantable,
        EloquentJoin,
        Activatable,
        Searchable,
        BelongsToCountry,
        BelongsToVendor,
        BelongsToUser,
        SoftDeletes;

    protected $perPage = 8;

    protected $hidden = [
        'deleted_at', 'drafted_at'
    ];

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

    public function toSearchArray()
    {
        $this->load('country', 'vendor');

        return Arr::except($this->toArray(), ['vendor.logo']);
    }

    public function quoteDiscount()
    {
        return $this->morphOne(QuoteDiscount::class, 'discountable');
    }

    public function scopeQuoteAcceptable($query, BaseQuote $quote)
    {
        return $query
            ->whereHas('country', fn ($query) => $query->whereId($quote->country_id))
            ->whereHas('vendor', fn ($query) => $query->whereId($quote->vendor_id));
    }

    public function getDiscountTypeAttribute()
    {
        return class_basename($this);
    }

    public function getItemNameAttribute()
    {
        return $this->name;
    }
}
