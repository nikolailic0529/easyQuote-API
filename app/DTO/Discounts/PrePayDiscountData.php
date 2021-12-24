<?php

namespace App\DTO\Discounts;

use Spatie\DataTransferObject\DataTransferObject;

final class PrePayDiscountData extends DataTransferObject
{
    public float $value;

    public ?float $applicableValue = null;
}
