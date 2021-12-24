<?php

namespace App\DTO\WorldwideQuote;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class DistributorQuoteDiscountCollection extends DataTransferObjectCollection
{
    public function current(): DistributorQuoteDiscountData
    {
        return parent::current();
    }
}
