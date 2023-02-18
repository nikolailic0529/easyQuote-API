<?php

namespace App\Domain\Rescue\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObjectCollection;

class S4AddressCollection extends DataTransferObjectCollection
{
    public function current(): S4Address
    {
        return parent::current();
    }
}
