<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class DistributorQuoteCountryMarginTaxCollection extends DataTransferObjectCollection
{
    public function current(): DistributorQuoteCountryMarginTaxData
    {
        return parent::current();
    }
}
