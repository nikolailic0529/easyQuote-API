<?php

namespace App\Traits\Discount;

use App\Models\Quote\Discount\{
    MultiYearDiscount,
    PrePayDiscount,
    PromotionalDiscount,
    SND
};
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasDiscounts
{
    public function multiYearDiscounts(): HasMany
    {
        return $this->hasMany(MultiYearDiscount::class);
    }

    public function prePayDiscounts(): HasMany
    {
        return $this->hasMany(PrePayDiscount::class);
    }

    public function promotionalDiscounts(): HasMany
    {
        return $this->hasMany(PromotionalDiscount::class);
    }

    public function SNDs(): HasMany
    {
        return $this->hasMany(SND::class);
    }
}
