<?php

namespace App\Domain\Discount\Models;

use App\Domain\Authentication\Concerns\Multitenantable;
use App\Domain\Country\Concerns\BelongsToCountry;
use App\Domain\Country\Models\Country;
use App\Domain\Rescue\Models\BaseQuote;
use App\Domain\Rescue\Models\Discount as QuoteDiscount;
use App\Domain\Shared\Eloquent\Concerns\Activatable;
use App\Domain\Shared\Eloquent\Concerns\Searchable;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\Shared\Eloquent\Contracts\ActivatableInterface;
use App\Domain\User\Concerns\BelongsToUser;
use App\Domain\User\Models\User;
use App\Domain\Vendor\Concerns\BelongsToVendor;
use App\Domain\Vendor\Models\Vendor;
use Fico7489\Laravel\EloquentJoin\Traits\EloquentJoin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property Country $country
 * @property Vendor $vendor
 * @property User $user
 */
abstract class Discount extends Model implements ActivatableInterface
{
    use Uuid;
    use Multitenantable;
    use EloquentJoin;
    use Activatable;
    use Searchable;
    use BelongsToCountry;
    use BelongsToVendor;
    use BelongsToUser;
    use SoftDeletes;

    protected $perPage = 8;

    protected $hidden = [
        'deleted_at', 'drafted_at',
    ];

    protected static function boot()
    {
        parent::boot();

        /*
         * Create Pivot model instance for Polymorphic relations on Quotes
         */
        static::creating(function (Discount $model) {
            $model->quoteDiscount()->create([]);
        });
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
