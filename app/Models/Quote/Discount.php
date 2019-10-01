<?php namespace App\Models\Quote;

use App\Models\UuidModel;
use Str;

class Discount extends UuidModel
{
    protected $hidden = [
        'pivot', 'discountable_type', 'discountable_id'
    ];

    protected $appends = [
        'discount_type'
    ];

    public function discountable()
    {
        return $this->morphTo();
    }

    public function quotes()
    {
        return $this->belongsToMany(Quote::class);
    }

    public function getDiscountTypeAttribute()
    {
        return Str::after($this->getAttribute('discountable_type'), 'Discount\\');
    }

    public function getDurationAttribute()
    {
        return $this->pivot->duration;
    }
}
