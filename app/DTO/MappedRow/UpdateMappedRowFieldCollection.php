<?php

namespace App\DTO\MappedRow;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class UpdateMappedRowFieldCollection extends DataTransferObjectCollection
{
    public function current(): MappedRowFieldData
    {
        return parent::current();
    }
}
