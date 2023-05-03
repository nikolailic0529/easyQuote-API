<?php

namespace App\Domain\Rescue\Quote;

trait HasCustomDiscountAttribute
{
    public function initializeHasCustomDiscountAttribute()
    {
        $this->fillable = array_merge($this->fillable, ['custom_discount']);
        $this->casts = array_merge($this->casts, ['custom_discount' => 'float']);
    }

    public function setCustomDiscountAttribute($value): void
    {
        $this->attributes['custom_discount'] = (float) $value;
        $this->discounts()->detach();
    }

    public function resetCustomDiscount(): bool
    {
        return $this->fresh()->forceFill(['custom_discount' => 0])->save();
    }
}
