<?php

namespace App\Models\Quote\Discount;

use App\Traits\{
    Discount\HasValueAttribute,
    Activity\LogsActivity
};

class SND extends Discount
{
    use HasValueAttribute, LogsActivity;

    protected $table = 'sn_discounts';

    protected $fillable = ['country_id', 'vendor_id', 'name'];

    protected static $logAttributes = [
        'name', 'country.name', 'vendor.name', 'value'
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;
}
