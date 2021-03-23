<?php

namespace App\DTO\WorldwideQuote;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class DistributorQuoteCountryMarginTaxCollection extends DataTransferObjectCollection
{
    public function current(): DistributorQuoteCountryMarginTaxData
    {
        return parent::current();
    }
}
