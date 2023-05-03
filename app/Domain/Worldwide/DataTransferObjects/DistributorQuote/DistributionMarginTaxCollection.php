<?php

namespace App\Domain\Worldwide\DataTransferObjects\DistributorQuote;

use Spatie\DataTransferObject\DataTransferObjectCollection;

class DistributionMarginTaxCollection extends DataTransferObjectCollection
{
    public function current(): DistributionMarginTax
    {
        return parent::current();
    }
}
