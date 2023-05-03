<?php

namespace App\Domain\Discount\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasDiscounts
{
    public function multiYearDiscounts(): HasMany
    {
        return $this->hasMany(\App\Domain\Discount\Models\MultiYearDiscount::class);
    }

    public function prePayDiscounts(): HasMany
    {
        return $this->hasMany(\App\Domain\Discount\Models\PrePayDiscount::class);
    }

    public function promotionalDiscounts(): HasMany
    {
        return $this->hasMany(\App\Domain\Discount\Models\PromotionalDiscount::class);
    }

    public function SNDs(): HasMany
    {
        return $this->hasMany(\App\Domain\Discount\Models\SND::class);
    }
}
