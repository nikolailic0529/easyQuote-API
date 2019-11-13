<?php

namespace App\Traits\Discount;

use App\Models\Quote\Discount\{
    MultiYearDiscount,
    PrePayDiscount,
    PromotionalDiscount,
    SND
};

trait HasDiscounts
{
    public function multiYearDiscounts()
    {
        return $this->hasMany(MultiYearDiscount::class);
    }

    public function prePayDiscounts()
    {
        return $this->hasMany(PrePayDiscount::class);
    }

    public function promotionalDiscounts()
    {
        return $this->hasMany(PromotionalDiscount::class);
    }

    public function SNDs()
    {
        return $this->hasMany(SND::class);
    }
}
