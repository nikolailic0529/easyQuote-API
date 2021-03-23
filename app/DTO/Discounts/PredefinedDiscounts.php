<?php

namespace App\DTO\Discounts;

use App\Models\Quote\Discount\MultiYearDiscount;
use App\Models\Quote\Discount\PrePayDiscount;
use App\Models\Quote\Discount\PromotionalDiscount;
use App\Models\Quote\Discount\SND;
use Spatie\DataTransferObject\DataTransferObject;

final class PredefinedDiscounts extends DataTransferObject
{
    public ?MultiYearDiscount $multiYearDiscount = null;

    public ?PrePayDiscount $prePayDiscount = null;

    public ?PromotionalDiscount $promotionalDiscount = null;

    public ?SND $snDiscount = null;
}
