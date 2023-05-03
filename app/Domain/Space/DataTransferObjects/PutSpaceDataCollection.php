<?php

namespace App\Domain\Space\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class PutSpaceDataCollection extends DataTransferObjectCollection
{
    public function current(): PutSpaceData
    {
        return parent::current();
    }
}
