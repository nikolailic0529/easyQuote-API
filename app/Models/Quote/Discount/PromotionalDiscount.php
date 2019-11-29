<?php

namespace App\Models\Quote\Discount;

use App\Traits\Discount\HasValueAttribute;
use Spatie\Activitylog\Traits\LogsActivity;

class PromotionalDiscount extends Discount
{
    use HasValueAttribute, LogsActivity;

    protected $fillable = [
        'minimum_limit'
    ];

    protected $casts = [
        'minimum_limit' => 'decimal,2'
    ];

    protected static $logAttributes = [
        'name', 'country.name', 'vendor.name', 'value', 'minimum_limit'
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;
}
