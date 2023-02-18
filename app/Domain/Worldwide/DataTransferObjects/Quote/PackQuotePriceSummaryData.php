<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use App\Domain\Discount\DataTransferObjects\ImmutablePriceSummaryData;
use Spatie\DataTransferObject\DataTransferObject;

final class PackQuotePriceSummaryData extends DataTransferObject
{
    public string $worldwide_quote_id;

    public ImmutablePriceSummaryData $quote_price_summary;
}
