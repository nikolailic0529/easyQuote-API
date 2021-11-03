<?php

namespace App\Models\QuoteFile;

use App\Models\Asset;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\Quote\WorldwideDistribution;
use App\Models\Quote\WorldwideQuote;
use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\WorldwideQuoteAsset;
use App\Traits\Uuid;
use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasOneDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property string|null $quote_file_id
 * @property string|null $replicated_mapped_row_id
 * @property string|null $product_no
 * @property string|null $service_sku
 * @property string|null $description
 * @property string|null $serial_no
 * @property string|null $date_from
 * @property string|null $date_to
 * @property int|null $qty
 * @property float|null $price
 * @property float|null $original_price
 * @property string|null $pricing_document
 * @property string|null $system_handle
 * @property string|null $searchable
 * @property string|null $service_level_description
 * @property bool|null $is_selected
 *
 * @property-read bool|null $is_customer_exclusive_asset
 * @property-read bool|null $same_worldwide_quote_assets_exists
 * @property-read bool|null $same_mapped_rows_exists
 * @property-read Company|null $company
 * @property-read WorldwideQuoteVersion|null $worldwideQuoteVersion
 * @property-read WorldwideDistribution|null $worldwideDistributorQuote
 * @property-read bool|null $exists_in_selected_groups
 * @property-read Company|null $owned_by_customer
 */
class MappedRow extends Model
{
    use Uuid, Compoships, HasRelationships;

    protected $guarded = [];

    protected $casts = [
//        'price' => 'decimal:2',
        'is_selected' => 'boolean',
    ];

    protected $hidden = [
        'laravel_through_key', 'pivot',
    ];

    public function quoteFile(): BelongsTo
    {
        return $this->belongsTo(QuoteFile::class);
    }

    public function sameMappedRows(): HasMany
    {
        return $this->hasMany(related: MappedRow::class, foreignKey: ['serial_no', 'product_no'], localKey: ['serial_no', 'product_no']);
    }

    public function sameWorldwideQuoteAssets(): HasMany
    {
        return $this->hasMany(related: WorldwideQuoteAsset::class, foreignKey: ['serial_no', 'sku'], localKey: ['serial_no', 'product_no']);
    }

    public function worldwideDistributorQuoteRowsGroups(): BelongsToMany
    {
        return $this->belongsToMany(related: DistributionRowsGroup::class, relatedPivotKey: 'rows_group_id');
    }

    public function worldwideDistributorQuote(): HasOneThrough
    {
        return $this->hasOneThrough(
            related: WorldwideDistribution::class,
            through: QuoteFile::class,
            firstKey: 'id',
            secondKey: 'distributor_file_id',
            localKey: 'quote_file_id'
        );
    }

    public function worldwideQuoteVersion(): HasOneDeep
    {
        return $this->hasOneDeep(
            related: WorldwideQuoteVersion::class,
            through: [
                WorldwideDistribution::class,
            ],
            foreignKeys: [
                'distributor_file_id',
                'id',
            ],
            localKeys: [
                'quote_file_id',
                'worldwide_quote_id',
            ]);
    }

    public function company(): HasOneDeep
    {
        return $this->hasOneDeep(
            related: Company::class,
            through: [
                WorldwideDistribution::class,
                WorldwideQuoteVersion::class,
                WorldwideQuote::class,
                Opportunity::class,
            ],
            foreignKeys: [
                'distributor_file_id',
                'id',
                'id',
                'id',
                'id',
            ],
            localKeys: [
                'quote_file_id',
                'worldwide_quote_id',
                'worldwide_quote_id',
                'opportunity_id',
                'primary_account_id',
            ]);
    }

    public function distributionRowsGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            related: DistributionRowsGroup::class,
            table: 'distribution_rows_group_mapped_row',
            foreignPivotKey: 'mapped_row_id',
            relatedPivotKey: 'rows_group_id'
        );
    }
}
