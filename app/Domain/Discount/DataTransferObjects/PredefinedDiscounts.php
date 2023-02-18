<?php

namespace App\Domain\Discount\DataTransferObjects;

use App\Domain\Discount\Models\MultiYearDiscount;
use App\Domain\Discount\Models\PrePayDiscount;
use App\Domain\Discount\Models\PromotionalDiscount;
use App\Domain\Discount\Models\SND;
use Spatie\DataTransferObject\DataTransferObject;

final class PredefinedDiscounts extends DataTransferObject
{
    public ?MultiYearDiscount $multiYearDiscount = null;

    public ?PrePayDiscount $prePayDiscount = null;

    public ?PromotionalDiscount $promotionalDiscount = null;

    public ?SND $snDiscount = null;
}
