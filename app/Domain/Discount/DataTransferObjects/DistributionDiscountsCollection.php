<?php

namespace App\Domain\Discount\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class DistributionDiscountsCollection extends DataTransferObjectCollection
{
    public function current(): DistributionDiscountsData
    {
        return parent::current();
    }
}
