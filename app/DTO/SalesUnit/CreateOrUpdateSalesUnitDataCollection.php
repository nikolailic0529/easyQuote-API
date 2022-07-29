<?php

namespace App\DTO\SalesUnit;

use Spatie\DataTransferObject\DataTransferObjectCollection;

final class CreateOrUpdateSalesUnitDataCollection extends DataTransferObjectCollection
{
    public function current(): CreateOrUpdateSalesUnitData
    {
        return parent::current();
    }
}