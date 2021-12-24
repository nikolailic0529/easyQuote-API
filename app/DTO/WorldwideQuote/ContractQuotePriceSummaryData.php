<?php

namespace App\DTO\WorldwideQuote;

use App\DTO\Discounts\ImmutablePriceSummaryData;
use Spatie\DataTransferObject\DataTransferObject;

final class ContractQuotePriceSummaryData extends DataTransferObject
{
    public string $worldwide_quote_id;

    public ImmutablePriceSummaryData $quote_price_summary;

    /**
     * @var \App\DTO\WorldwideQuote\DistributorQuotePriceSummary[]
     */
    public array $worldwide_distributions;
}
