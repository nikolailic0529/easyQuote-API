<?php

namespace App\Domain\Worldwide\DataTransferObjects\DistributorQuote;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class DistributionDetailsCollection extends DataTransferObjectCollection
{
    public function current(): DistributionDetailsData
    {
        return parent::current();
    }
}
