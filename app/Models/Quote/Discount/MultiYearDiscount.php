<?php

namespace App\Models\Quote\Discount;

use App\Traits\{
    Discount\HasDurationsAttribute,
    Activity\LogsActivity
};

class MultiYearDiscount extends Discount
{
    use HasDurationsAttribute, LogsActivity;

    protected $fillable = ['country_id', 'vendor_id', 'name'];

    protected static $logAttributes = [
        'name', 'country.name', 'vendor.name', 'duration', 'value'
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;
}
