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

    public function getValue($total)
    {
        $total = (float) $total;
        $discount = $this->discountable;

        if($discount instanceof MultiYearDiscount || $discount instanceof PrePayDiscount) {
            $durations = collect($discount->durations);
            $percentage = (float) ($durations->where('duration', $this->duration)->first()['value'] ?? $durations->first()['value']);

            return $percentage;
        }

        if($discount instanceof PromotionalDiscount) {
            $percentage = (float) $discount->value;
            $limit = $discount->minimum_limit;

            if($limit <= $total) {
                return $percentage;
            }

            return 0;
        }

        if($discount instanceof SND) {
            return (float) $discount->value;
        }
    }

    public function calculateDiscount($value, $total)
    {
        $value = (float) $value;
        $total = (float) $total;
        $discount = $this->discountable;

        if($discount instanceof MultiYearDiscount || $discount instanceof PrePayDiscount) {
            $durations = collect($discount->durations);
            $percentage = (float) ($durations->where('duration', $this->duration)->first()['value'] ?? $durations->first()['value']);

            return $value * $percentage / 100;
        }

        if($discount instanceof PromotionalDiscount) {
            $percentage = (float) $discount->value;
            $limit = $discount->minimum_limit;

            if($limit <= $total) {
                return $value * $percentage / 100;
            }

            return 0;
        }

        if($discount instanceof SND) {
            $percentage = (float) $discount->value;

            return $value * $percentage / 100;
        }
    }

    public function scopeDiscountType($query, string $class)
    {
        return $query->whereHasMorph('discountable', $class);
    }
}
