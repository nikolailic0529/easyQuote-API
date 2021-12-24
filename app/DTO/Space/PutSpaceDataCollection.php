<?php

namespace App\DTO\Space;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class PutSpaceDataCollection extends DataTransferObjectCollection
{
    public function current(): PutSpaceData
    {
        return parent::current();
    }
}
