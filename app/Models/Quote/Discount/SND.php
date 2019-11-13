<?php

namespace App\Models\Quote\Discount;

class SND extends Discount
{
    protected $table = 'sn_discounts';

    protected $fillable = [
        'value'
    ];
}
