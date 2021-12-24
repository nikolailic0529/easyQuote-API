<?php

namespace App\DTO\Discounts;

use Spatie\DataTransferObject\DataTransferObject;

final class MultiYearDiscountData extends DataTransferObject
{
    public float $value;

    public ?float $applicableValue = null;
}
