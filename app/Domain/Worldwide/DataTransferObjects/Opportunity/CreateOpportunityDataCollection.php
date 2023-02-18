<?php

namespace App\Domain\Worldwide\DataTransferObjects\Opportunity;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class CreateOpportunityDataCollection extends DataTransferObjectCollection
{
    public function current(): CreateOpportunityData
    {
        return parent::current();
    }
}
