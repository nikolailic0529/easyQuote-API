<?php

namespace App\DTO;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class DistributionExpiryDateCollection extends DataTransferObjectCollection
{
    public function current(): DistributionExpiryDate
    {
        return parent::current();
    }
}
