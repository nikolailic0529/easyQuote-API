<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote\Export;

use Spatie\DataTransferObject\DataTransferObject;

final class QuotePriceData extends DataTransferObject
{
    public float $total_price_value = 0.0;

    public float $total_price_value_after_margin = 0.0;

    public float $final_total_price_value = 0.0;

    public float $final_total_price_value_excluding_tax = 0.0;

    public float $applicable_discounts_value = 0.0;

    public float $price_value_coefficient = 0.0;
}
