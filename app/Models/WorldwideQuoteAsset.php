<?php

namespace App\Models;

use App\Models\Data\Currency;
use App\Models\Quote\BaseWorldwideQuote;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class WorldwideQuoteAsset
 *
 * @property string|null $replicated_asset_id
 * @property string|null $worldwide_quote_id
 * @property string|null $worldwide_quote_type
 * @property string|null $vendor_id
 * @property string|null $machine_address_id
 * @property string|null $buy_currency_id
 * @property string|null $country
 * @property string|null $serial_no
 * @property string|null $sku
 * @property string|null $service_sku
 * @property string|null $product_name
 * @property string|null $expiry_date
 * @property string|null $service_level_description
 * @property float|null $price
 * @property float|null $original_price
 * @property float|null $exchange_rate_value
 * @property float|null $exchange_rate_margin
 * @property string|null $vendor_short_code
 * @property array|null $service_level_data
 * @property bool|null $is_selected
 *
 * @property BaseWorldwideQuote|null $worldwideQuote
 * @property Vendor|null $vendor
 * @property Address|null $machineAddress
 * @property Currency|null $buyCurrency
 * @property-read string|null $date_to
 * @property-read string|null $machine_address_string
 */
class WorldwideQuoteAsset extends Model
{
    use Uuid;

    public $timestamps = false;

    protected $guarded = [];

    protected $hidden = [
        'worldwide_quote_id', 'worldwide_quote_type', 'pivot'
    ];

    protected $casts = [
        'service_level_data' => 'array'
    ];

    public function worldwideQuote(): MorphTo
    {
        return $this->morphTo();
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
}
