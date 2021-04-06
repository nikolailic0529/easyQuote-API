<?php

namespace App\Models\Quote\Discount;

use App\Traits\{
    Discount\HasValueAttribute,
    Activity\LogsActivity
};

/**
 * @property string|null $name
 */
class PromotionalDiscount extends Discount
{
    use HasValueAttribute, LogsActivity;

    protected $fillable = ['country_id', 'vendor_id', 'name', 'minimum_limit'];

    protected $casts = [
        'minimum_limit' => 'decimal:2'
    ];

    protected static $logAttributes = [
        'name', 'country.name', 'vendor.name', 'value', 'minimum_limit'
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;
}
