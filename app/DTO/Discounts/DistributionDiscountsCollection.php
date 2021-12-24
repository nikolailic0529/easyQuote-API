<?php

namespace App\DTO\Discounts;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class DistributionDiscountsCollection extends DataTransferObjectCollection
{
    public function current(): DistributionDiscountsData
    {
        return parent::current();
    }
}
