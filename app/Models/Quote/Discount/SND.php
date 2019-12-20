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

    protected static $logAttributes = [
        'name', 'country.name', 'vendor.name', 'value'
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;
}
