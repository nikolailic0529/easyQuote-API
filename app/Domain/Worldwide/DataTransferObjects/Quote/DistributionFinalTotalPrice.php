<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use Spatie\DataTransferObject\DataTransferObject;

final class DistributionFinalTotalPrice extends DataTransferObject
{
    public float $final_total_price_value;

    public float $applicable_discounts_value;
}
