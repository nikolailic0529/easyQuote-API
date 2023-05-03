<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class OpportunityContactDataCollection extends DataTransferObjectCollection
{
    public function current(): OpportunityContactData
    {
        return parent::current();
    }
}
