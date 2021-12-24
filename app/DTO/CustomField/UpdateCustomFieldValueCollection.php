<?php

namespace App\DTO\CustomField;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class UpdateCustomFieldValueCollection extends DataTransferObjectCollection
{
    public function current(): UpdateCustomFieldValueData
    {
        return parent::current();
    }
}
