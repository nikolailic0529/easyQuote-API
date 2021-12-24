<?php

namespace App\DTO\WorldwideQuote;

use Spatie\DataTransferObject\DataTransferObject;

final class QuotePriceInputData extends DataTransferObject
{
    public float $total_price;

    public float $buy_price;

    public float $margin_value;

    public float $tax_value;

    public static function immutable(array $parameters = []): ImmutableQuotePriceInputData
    {
        return new ImmutableQuotePriceInputData(
            new QuotePriceInputData($parameters)
        );
    }
}
