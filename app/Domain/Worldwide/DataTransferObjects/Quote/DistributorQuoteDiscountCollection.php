<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class DistributorQuoteDiscountCollection extends DataTransferObjectCollection
{
    public function current(): DistributorQuoteDiscountData
    {
        return parent::current();
    }
}
