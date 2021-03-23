<?php

namespace App\DTO\Opportunity;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class CreateOpportunityDataCollection extends DataTransferObjectCollection
{
    public function current(): CreateOpportunityData
    {
        return parent::current();
    }
}
