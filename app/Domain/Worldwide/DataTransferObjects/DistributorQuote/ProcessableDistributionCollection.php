<?php

namespace App\Domain\Worldwide\DataTransferObjects\DistributorQuote;

use Spatie\DataTransferObject\DataTransferObjectCollection;

class ProcessableDistributionCollection extends DataTransferObjectCollection
{
    public function current(): ProcessableDistribution
    {
        return parent::current();
    }
}
