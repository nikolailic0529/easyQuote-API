<?php

namespace App\Domain\DocumentMapping\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class UpdateMappedRowFieldCollection extends DataTransferObjectCollection
{
    public function current(): MappedRowFieldData
    {
        return parent::current();
    }
}
