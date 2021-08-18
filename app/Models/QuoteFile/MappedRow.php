<?php

namespace App\Models\QuoteFile;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
 */
class MappedRow extends Model
{
    use Uuid;

    protected $guarded = [];

    protected $casts = [
//        'price' => 'decimal:2',
        'is_selected' => 'boolean',
    ];

    protected $hidden = [
        'laravel_through_key', 'pivot'
    ];

    public function quoteFile(): BelongsTo
    {
        return $this->belongsTo(QuoteFile::class);
    }
}
