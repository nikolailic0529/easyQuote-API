<?php namespace App\Models\Quote;

use App\Models\Quote\Discount \ {
    MultiYearDiscount,
    PrePayDiscount,
    PromotionalDiscount,
    SND
};
use App\Models\UuidModel;
use Str;

class Discount extends UuidModel
{
    protected $hidden = [
        'pivot', 'discountable_type', 'discountable_id'
    ];

    protected $appends = [
        'discount_type', 'duration'
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

    public function calculate($value, $total)
    {
        $value = (float) $value;
        $total = (float) $total;
        $discount = $this->discountable;

        if($discount instanceof MultiYearDiscount || $discount instanceof PrePayDiscount) {
            $durations = collect($discount->durations);
            $percentage = $durations->where('duration', 3)->first()['value'] ?? $durations->first()['value'];

            return $value - ($value * $percentage / 100);
        }

        if($discount instanceof PromotionalDiscount) {
            $percentage = $discount->value;
            $limit = $discount->minimum_limit;

            if($limit <= $total) {
                return $value - ($value * $percentage / 100);
            }

            return $value;
        }

        if($discount instanceof SND) {
            $percentage = $discount->value;

            return $value - ($value * $percentage / 100);
        }
    }
}
