<?php

namespace App\Models\Quote\Discount;

use App\Models\Quote\WorldwideDistribution;
use App\Models\Quote\WorldwideQuoteVersion;
use App\Traits\{
    Discount\HasValueAttribute,
    Activity\LogsActivity
};
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property string|null $name
 */
class SND extends Discount
{
    use HasValueAttribute, LogsActivity, HasRelationships;

    protected $table = 'sn_discounts';

    protected $fillable = ['country_id', 'vendor_id', 'name'];

    protected static $logAttributes = [
        'name', 'country.name', 'vendor.name', 'value'
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    public function worldwidePackQuoteVersions(): HasMany
    {
        return $this->hasMany(WorldwideQuoteVersion::class, 'sn_discount_id');
    }

    public function worldwidePackQuotes(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->worldwidePackQuoteVersions(), (new WorldwideQuoteVersion())->worldwideQuote());
    }

    public function worldwideDistributorQuotes(): HasMany
    {
        return $this->hasMany(WorldwideDistribution::class, 'sn_discount_id');
    }

    public function worldwideContractQuoteVersions(): HasManyThrough
    {
        return $this->hasManyThrough(related: WorldwideQuoteVersion::class, through: WorldwideDistribution::class, firstKey: 'sn_discount_id', secondKey: 'id', secondLocalKey: 'worldwide_quote_id');
    }

    public function worldwideContractQuotes(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->worldwideContractQuoteVersions(), (new WorldwideQuoteVersion())->worldwideQuote());
    }

    public function pivotDiscounts(): HasMany
    {
        return $this->hasMany(\App\Models\Quote\Discount::class, 'discountable_id');
    }

    public function rescueQuotes(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->pivotDiscounts(), (new \App\Models\Quote\Discount)->quotes());
    }

    public function rescueQuoteVersions(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->pivotDiscounts(), (new \App\Models\Quote\Discount)->quoteVersions());
    }
}
