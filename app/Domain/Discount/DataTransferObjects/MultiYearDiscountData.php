<?php

namespace App\Domain\Discount\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;

final class MultiYearDiscountData extends DataTransferObject
{
    public float $value;

    public ?float $applicableValue = null;
}
