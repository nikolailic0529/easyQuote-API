<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class OpportunityAddressDataCollection extends DataTransferObjectCollection
{
    public function current(): OpportunityAddressData
    {
        return parent::current();
    }
}
