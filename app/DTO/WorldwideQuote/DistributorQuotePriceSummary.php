<?php

namespace App\DTO\WorldwideQuote;

use App\DTO\Discounts\ImmutablePriceSummaryData;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class DistributorQuotePriceSummary extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $worldwide_distribution_id;

    public ?int $index = null;

    public ImmutablePriceSummaryData $price_summary;
}
