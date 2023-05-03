<?php

namespace App\Domain\Discount\Models;

use App\Domain\Activity\Concerns\LogsActivity;
use App\Domain\Discount\Concerns\HasDurationsAttribute;
use App\Domain\Worldwide\Models\WorldwideDistribution;
use App\Domain\Worldwide\Models\WorldwideQuoteVersion;
use App\Foundation\Support\Elasticsearch\Contracts\SearchableEntity;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property string $name
 * @property int $duration
 * @property float $value
 */
class MultiYearDiscount extends Discount implements SearchableEntity
{
    use HasDurationsAttribute;
    use LogsActivity;
    use HasRelationships;

    protected $fillable = ['country_id', 'vendor_id', 'name'];

    protected static $logAttributes = [
        'name', 'country.name', 'vendor.name', 'duration', 'value',
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    public function worldwidePackQuoteVersions(): HasMany
    {
        return $this->hasMany(WorldwideQuoteVersion::class, 'multi_year_discount_id');
    }

    public function worldwidePackQuotes(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->worldwidePackQuoteVersions(), (new WorldwideQuoteVersion())->worldwideQuote());
    }

    public function worldwideDistributorQuotes(): HasMany
    {
        return $this->hasMany(WorldwideDistribution::class, 'multi_year_discount_id');
    }

    public function worldwideContractQuoteVersions(): HasManyThrough
    {
        return $this->hasManyThrough(related: WorldwideQuoteVersion::class, through: WorldwideDistribution::class, secondKey: 'id', secondLocalKey: 'worldwide_quote_id');
    }

    public function worldwideContractQuotes(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->worldwideContractQuoteVersions(), (new WorldwideQuoteVersion())->worldwideQuote());
    }

    public function pivotDiscounts(): HasMany
    {
        return $this->hasMany(\App\Domain\Rescue\Models\Discount::class, 'discountable_id');
    }

    public function rescueQuotes(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->pivotDiscounts(),
            (new \App\Domain\Rescue\Models\Discount())->quotes());
    }

    public function rescueQuoteVersions(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->pivotDiscounts(),
            (new \App\Domain\Rescue\Models\Discount())->quoteVersions());
    }

    public function toSearchArray(): array
    {
        return [
            'name' => $this->name,
            'country_name' => $this->country?->name,
            'country_code' => $this->country?->iso_3166_2,
            'vendor_name' => $this->vendor?->name,
            'vendor_code' => $this->vendor?->short_code,
            'duration' => "$this->duration year",
            'value' => "$this->value%",
        ];
    }
}
