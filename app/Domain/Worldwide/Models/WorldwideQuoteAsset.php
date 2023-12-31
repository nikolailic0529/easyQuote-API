<?php

namespace App\Domain\Worldwide\Models;

use App\Domain\Address\Models\Address;
use App\Domain\Company\Models\Company;
use App\Domain\Currency\Models\Currency;
use App\Domain\DocumentMapping\Models\MappedRow;
use App\Domain\Vendor\Models\Vendor;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Awobaz\Compoships\Compoships;
use Awobaz\Compoships\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Staudenmeir\EloquentHasManyDeep\HasOneDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * Class WorldwideQuoteAsset.
 *
 * @property string|null                                          $replicated_asset_id
 * @property string|null                                          $worldwide_quote_id
 * @property string|null                                          $worldwide_quote_type
 * @property string|null                                          $vendor_id
 * @property string|null                                          $machine_address_id
 * @property string|null                                          $buy_currency_id
 * @property string|null                                          $country
 * @property string|null                                          $serial_no
 * @property string|null                                          $sku
 * @property string|null                                          $service_sku
 * @property string|null                                          $product_name
 * @property string|null                                          $expiry_date
 * @property string|null                                          $service_level_description
 * @property float|null                                           $buy_price                          Buy Price
 * @property float|null                                           $buy_price_margin                   Buy Price Margin
 * @property float|null                                           $original_price                     List Price
 * @property float|null                                           $price                              Selling price
 * @property float|null                                           $exchange_rate_value
 * @property float|null                                           $exchange_rate_margin
 * @property string|null                                          $vendor_short_code
 * @property array|null                                           $service_level_data
 * @property bool|null                                            $is_selected
 * @property bool|null                                            $is_warranty_checked
 * @property bool|null                                            $is_serial_number_generated
 * @property int|null                                             $entity_order
 * @property \App\Domain\Worldwide\Models\BaseWorldwideQuote|null $worldwideQuote
 * @property Vendor|null                                          $vendor
 * @property Address|null                                         $machineAddress
 * @property Currency|null                                        $buyCurrency
 * @property string|null                                          $date_to
 * @property string|null                                          $machine_address_string
 * @property bool|null                                            $is_customer_exclusive_asset
 * @property bool|null                                            $same_worldwide_quote_assets_exists
 * @property bool|null                                            $same_mapped_rows_exists
 * @property Company|null                                         $company
 * @property WorldwideQuoteVersion|null                           $worldwideQuoteVersion
 * @property bool|null                                            $exists_in_selected_groups
 * @property Company|null                                         $owned_by_customer
 */
class WorldwideQuoteAsset extends Model
{
    use Uuid;
    use Compoships;
    use HasRelationships;

    protected $guarded = [];

    protected $hidden = [
        'worldwide_quote_id', 'worldwide_quote_type', 'pivot',
    ];

    protected $casts = [
        'service_level_data' => 'array',
    ];

    public function worldwideQuote(): MorphTo
    {
        return $this->morphTo();
    }

    public function worldwideQuoteVersion(): BelongsTo
    {
        return $this->belongsTo(WorldwideQuoteVersion::class, 'worldwide_quote_id');
    }

    public function replicatedAsset(): BelongsTo
    {
        return $this->belongsTo(WorldwideQuoteAsset::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function machineAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function buyCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function sameWorldwideQuoteAssets(): HasMany
    {
        return $this->hasMany(WorldwideQuoteAsset::class, ['serial_no', 'sku'], ['serial_no', 'sku']);
    }

    public function sameMappedRows(): HasMany
    {
        return $this->hasMany(MappedRow::class, ['serial_no', 'product_no'], ['serial_no', 'sku']);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(related: WorldwideQuoteAssetsGroup::class, table: 'worldwide_quote_assets_group_asset', foreignPivotKey: 'asset_id', relatedPivotKey: 'group_id');
    }

    public function company(): HasOneDeep
    {
        return $this->hasOneDeep(
            related: Company::class,
            through: [
                WorldwideQuoteVersion::class,
                WorldwideQuote::class,
                Opportunity::class,
            ],
            foreignKeys: [
                'id',
                'id',
                'id',
                'id',
            ],
            localKeys: [
                'worldwide_quote_id',
                'worldwide_quote_id',
                'opportunity_id',
                'primary_account_id',
            ]);
    }
}
