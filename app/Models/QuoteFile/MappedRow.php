<?php

namespace App\Models\QuoteFile;

use App\Models\WorldwideQuoteAsset;
use App\Traits\Uuid;
use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
 */
class MappedRow extends Model
{
    use Uuid, Compoships;

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
