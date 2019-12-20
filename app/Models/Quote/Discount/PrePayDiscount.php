<?php

namespace App\Models\Quote\Discount;

use App\Traits\{
    Discount\HasDurationsAttribute,
    Activity\LogsActivity
};

class PrePayDiscount extends Discount
{
    use HasDurationsAttribute, LogsActivity;

    protected static $logAttributes = [
        'name', 'country.name', 'vendor.name', 'duration', 'value'
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;
}
