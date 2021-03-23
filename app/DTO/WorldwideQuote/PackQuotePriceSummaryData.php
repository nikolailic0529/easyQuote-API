<?php

namespace App\DTO\WorldwideQuote;

use App\DTO\Discounts\ImmutablePriceSummaryData;
use Spatie\DataTransferObject\DataTransferObject;

final class PackQuotePriceSummaryData extends DataTransferObject
{
    public string $worldwide_quote_id;

    public ImmutablePriceSummaryData $quote_price_summary;
}
