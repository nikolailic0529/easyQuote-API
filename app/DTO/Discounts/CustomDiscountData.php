<?php

namespace App\DTO\Discounts;

use Spatie\DataTransferObject\DataTransferObject;

final class CustomDiscountData extends DataTransferObject
{
    public float $value;

    public ?float $applicableValue = null;

    public static function immutable(array $parameters = []): ImmutableCustomDiscountData
    {
        return new ImmutableCustomDiscountData(new CustomDiscountData($parameters));
    }
}
