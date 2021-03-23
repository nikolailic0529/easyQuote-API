<?php

namespace App\DTO\Discounts;

use Spatie\DataTransferObject\DataTransferObject;

final class PromotionalDiscountData extends DataTransferObject
{
    public float $minimum_limit;

    public float $value;

    public ?float $applicableValue = null;
}
