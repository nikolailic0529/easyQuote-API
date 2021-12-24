<?php

namespace App\DTO\WorldwideQuote;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class OpportunityAddressDataCollection extends DataTransferObjectCollection
{
    public function current(): OpportunityAddressData
    {
        return parent::current();
    }
}
