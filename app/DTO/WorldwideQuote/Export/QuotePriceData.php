<?php

namespace App\DTO\WorldwideQuote\Export;

use Spatie\DataTransferObject\DataTransferObject;

final class QuotePriceData extends DataTransferObject
{
    public float $total_price_value = 0.0;

    public float $final_total_price_value = 0.0;

    public float $applicable_discounts_value = 0.0;

    public float $price_value_coefficient = 0.0;
}
