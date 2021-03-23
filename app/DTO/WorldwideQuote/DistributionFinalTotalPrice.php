<?php

namespace App\DTO\WorldwideQuote;

use Spatie\DataTransferObject\DataTransferObject;

final class DistributionFinalTotalPrice extends DataTransferObject
{
    public float $final_total_price_value;

    public float $applicable_discounts_value;
}
