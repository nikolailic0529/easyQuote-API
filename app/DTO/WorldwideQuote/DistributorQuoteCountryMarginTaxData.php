<?php

namespace App\DTO\WorldwideQuote;

use App\DTO\Margin\ImmutableMarginTaxData;
use App\Models\Quote\WorldwideDistribution;
use Spatie\DataTransferObject\DataTransferObject;

final class DistributorQuoteCountryMarginTaxData extends DataTransferObject
{
    public WorldwideDistribution $worldwide_distribution;

    public ImmutableMarginTaxData $margin_tax_data;

    public ?int $index = null;
}
