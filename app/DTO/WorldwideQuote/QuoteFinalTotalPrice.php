<?php

namespace App\DTO\WorldwideQuote;

use Spatie\DataTransferObject\DataTransferObject;

final class QuoteFinalTotalPrice extends DataTransferObject
{
    public float $final_total_price_value = 0.0;

    public float $applicable_discounts_value = 0.0;
}
