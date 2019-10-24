<?php namespace App\Models\Quote\Discount;

use App\Traits\Discount\HasDurationsAttribute;

class MultiYearDiscount extends Discount
{
    use HasDurationsAttribute;

    protected $fillable = [
        'durations'
    ];

    protected $casts = [
        'durations' => 'array'
    ];
}
