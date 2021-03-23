<?php

namespace App\DTO;

use Spatie\DataTransferObject\DataTransferObjectCollection;

class ProcessableDistributionCollection extends DataTransferObjectCollection
{
    public function current(): ProcessableDistribution
    {
        return parent::current();
    }
}
