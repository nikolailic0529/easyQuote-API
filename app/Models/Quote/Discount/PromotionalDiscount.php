<?php

namespace App\Models\Quote\Discount;

class PromotionalDiscount extends Discount
{
    protected $fillable = [
        'value', 'minimum_limit'
    ];

    protected $casts = [
        'value' => 'decimal,2',
        'minimum_limit' => 'decimal,2'
    ];
}
