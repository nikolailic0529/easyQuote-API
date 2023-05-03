<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use App\Domain\Discount\DataTransferObjects\ImmutablePriceSummaryData;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class DistributorQuotePriceSummary extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     */
    public string $worldwide_distribution_id;

    public ?int $index = null;

    public ImmutablePriceSummaryData $price_summary;
}
