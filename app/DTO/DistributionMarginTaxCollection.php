<?php

namespace App\DTO;

use Spatie\DataTransferObject\DataTransferObjectCollection;

class DistributionMarginTaxCollection extends DataTransferObjectCollection
{
    public function current(): DistributionMarginTax
    {
        return parent::current();
    }
}
