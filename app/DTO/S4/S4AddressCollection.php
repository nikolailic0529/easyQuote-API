<?php

namespace App\DTO\S4;

use Spatie\DataTransferObject\DataTransferObjectCollection;

class S4AddressCollection extends DataTransferObjectCollection
{
    public function current(): S4Address
    {
        return parent::current();
    }
}