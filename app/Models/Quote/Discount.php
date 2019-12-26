<?php

namespace App\Models\Quote;

use App\Models\Quote\Discount\{
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
        'discount_type', 'duration', 'margin_percentage'
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
        return $this->attributes['duration'] ?? $this->pivot->duration ?? null;
    }

    public function getMarginPercentageAttribute()
    {
        $value = $this->attributes['margin_percentage'] ?? $this->pivot->margin_percentage ?? null;

        return isset($value) ? number_format($value, 2) : null;
    }

    public function getValue($total)
    {
        $total = (float) $total;
        $discount = $this->discountable;

        if ($discount instanceof MultiYearDiscount || $discount instanceof PrePayDiscount) {
            $durations = collect()->wrap($discount->durations);
            $percentage = (float) data_get($durations, 'duration.value');

            return $percentage;
        }

        if ($discount instanceof PromotionalDiscount) {
            $percentage = (float) $discount->value;
            $limit = $discount->minimum_limit;

            if ($limit <= $total) {
                return $percentage;
            }

            return 0;
        }

        if ($discount instanceof SND) {
            return (float) $discount->value;
        }

        return 0;
    }

    public function calculateDiscount($value, $total)
    {
        $value = (float) $value;

        return $value * $this->getValue($total) / 100;
    }

    public function scopeDiscountType($query, string $class)
    {
        return $query->whereHasMorph('discountable', $class);
    }

    public function toAttachableArray()
    {
        return [$this->id => $this->only('duration', 'margin_percentage')];
    }

    public function toDiscountableArray()
    {
        $id = $this->discountable_id;
        $duration = $this->duration;

        return compact('id', 'duration');
    }
}
