<?php

namespace App\DTO\WorldwideQuote;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class OpportunityContactDataCollection extends DataTransferObjectCollection
{
    public function current(): OpportunityContactData
    {
        return parent::current();
    }
}
