<?php namespace App\Models\Quote\Discount;

class PrePayDiscount extends Discount
{
    protected $fillable = [
        'durations'
    ];

    protected $casts = [
        'durations' => 'array'
    ];
}
