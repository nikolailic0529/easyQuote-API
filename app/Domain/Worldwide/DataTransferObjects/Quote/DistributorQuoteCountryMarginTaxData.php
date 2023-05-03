<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use App\Domain\Margin\DataTransferObjects\ImmutableMarginTaxData;
use App\Domain\Worldwide\Models\WorldwideDistribution;
use Spatie\DataTransferObject\DataTransferObject;

final class DistributorQuoteCountryMarginTaxData extends DataTransferObject
{
    public WorldwideDistribution $worldwide_distribution;

    public ImmutableMarginTaxData $margin_tax_data;

    public ?int $index = null;
}
