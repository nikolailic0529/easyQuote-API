<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use App\Domain\Discount\DataTransferObjects\ImmutablePriceSummaryData;
use Spatie\DataTransferObject\DataTransferObject;

final class ContractQuotePriceSummaryData extends DataTransferObject
{
    public string $worldwide_quote_id;

    public ImmutablePriceSummaryData $quote_price_summary;

    /**
     * @var \App\Domain\Worldwide\DataTransferObjects\Quote\DistributorQuotePriceSummary[]
     */
    public array $worldwide_distributions;
}
