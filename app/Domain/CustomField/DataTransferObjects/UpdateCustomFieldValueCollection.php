<?php

namespace App\Domain\CustomField\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class UpdateCustomFieldValueCollection extends DataTransferObjectCollection
{
    public function current(): UpdateCustomFieldValueData
    {
        return parent::current();
    }
}
