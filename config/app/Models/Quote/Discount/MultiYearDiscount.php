<?php namespace App\Models\Quote\Discount;

class MultiYearDiscount extends Discount
{
    protected $fillable = [
        'durations'
    ];

    protected $casts = [
        'durations' => 'array'
    ];
}
