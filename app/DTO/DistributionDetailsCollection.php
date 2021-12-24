<?php

namespace App\DTO;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class DistributionDetailsCollection extends DataTransferObjectCollection
{
    public function current(): DistributionDetailsData
    {
        return parent::current();
    }
}
