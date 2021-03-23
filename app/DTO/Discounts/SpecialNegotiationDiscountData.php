<?php

namespace App\DTO\Discounts;

use Spatie\DataTransferObject\DataTransferObject;

final class SpecialNegotiationDiscountData extends DataTransferObject
{
    public float $value;

    public ?float $applicableValue = null;
}
