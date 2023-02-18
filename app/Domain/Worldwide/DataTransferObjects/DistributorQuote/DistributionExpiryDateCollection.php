<?php

namespace App\Domain\Worldwide\DataTransferObjects\DistributorQuote;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class DistributionExpiryDateCollection extends DataTransferObjectCollection
{
    public function current(): DistributionExpiryDate
    {
        return parent::current();
    }
}
