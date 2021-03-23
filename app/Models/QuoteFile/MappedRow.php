<?php

namespace App\Models\QuoteFile;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property mixed $quote_file_id
 * @property string|null $replicated_mapped_row_id
 * @property mixed $product_no
 * @property string|null $service_sku
 * @property mixed $description
 * @property mixed $serial_no
 * @property mixed $date_from
 * @property mixed $date_to
 * @property mixed $qty
 * @property mixed $price
 * @property mixed $pricing_document
 * @property mixed $system_handle
 * @property mixed $searchable
 * @property mixed $service_level_description
 * @property mixed $is_selected
 */
class MappedRow extends Model
{
    use Uuid;

    public $timestamps = false;

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
