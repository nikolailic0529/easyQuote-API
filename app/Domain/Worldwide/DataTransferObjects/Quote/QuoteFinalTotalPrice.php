<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use Spatie\DataTransferObject\DataTransferObject;

final class QuoteFinalTotalPrice extends DataTransferObject
{
    public float $final_total_price_value = 0.0;

    public float $applicable_discounts_value = 0.0;
}
